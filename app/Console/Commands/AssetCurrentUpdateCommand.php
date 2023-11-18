<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssetCurrentUpdateCommand extends Command
{
    protected $signature = 'assets:current';
    protected $description = 'Update current price data';

    public function handle()
    {
        $results = [];
        $links = [
            "https://iss.moex.com/iss/engines/stock/markets/shares/boards/tqbr/securities.json", // stocks
            "https://iss.moex.com/iss/engines/stock/markets/shares/boards/tqtf/securities.json", // etf
            "https://iss.moex.com/iss/engines/stock/markets/index/boards/SNDX/securities.json", // index
            "https://iss.moex.com/iss/engines/stock/markets/index/boards/RTSI/securities.json", // index
            "https://iss.moex.com/iss/engines/currency/markets/index/securities.json", // currency
            "https://iss.moex.com/iss/engines/stock/markets/bonds/boards/tqob/securities.json",
        ];

        foreach ($links as $link) {
            $resp = json_decode(file_get_contents($link), true);
            if (!$resp || empty($resp['marketdata']) || empty($resp['marketdata']['data'])) {
                continue;
            }

            $cols = $resp['marketdata']['columns'];
            $cols = array_flip($cols);
            $priceCol = $cols['LCURRENTPRICE'] ?? $cols['CURRENTVALUE'];

            foreach ($resp['marketdata']['data'] as $row) {
                $ticker = $row[$cols['SECID']];
                $price = $row[$priceCol];

                if (str_contains($link, "/boards/tqob/")) {
                    $price *= 10;
                }

                $results[$ticker] = $price;
            }


        }

        $linksZo = [
            "https://iss.moex.com/iss/engines/stock/markets/bonds/boards/tqcb/securities.json",
        ];
        $usd = DB::selectOne("select * from `assets` where `ticker` = ?", ["USDFIX"]);

        foreach ($linksZo as $link) {
            $resp = json_decode(file_get_contents($link), true);
            if (!$resp || empty($resp['marketdata']) || empty($resp['marketdata']['data'])) {
                continue;
            }

            $cols = $resp['marketdata']['columns'];
            $cols = array_flip($cols);

            foreach ($resp['marketdata']['data'] as $row) {
                $ticker = $row[$cols['SECID']];
                $price = $row[$cols['LCURRENTPRICE']] / 100 * 1000 * $usd->price;
                $results[$ticker] = $price;
            }
        }

        $tickers = DB::select("select id, ticker from `assets`");
        foreach ($tickers as $row1) {
            $ticker = $row1->ticker;
            $aid = $row1->id;

            if (!empty($results[$ticker])) {
                DB::update("update `assets` set `price`=?, `updated_at` = now() where `id`=?",
                    [$results[$ticker], $aid]);

                $date = date('Y-m-d');
                $exists = DB::selectOne("select count(*) as c from `asset_history` where `asset_id` = ? and `date` = ?",
                    [$aid, $date]);

                if ($exists->c) {
                    DB::update("update `asset_history` set `close`=?, `updated_at` = now()
                            where `date`=? and `asset_id`=? and `low` is null", [$results[$ticker], $date, $aid]);
                } else {
                    DB::insert("insert into `asset_history` (`asset_id`, `close`, `date`)
                            values (?, ?, ?)", [$aid, $results[$ticker], $date]);
                }
            }
        }

        echo("OK" . PHP_EOL);
    }
}
