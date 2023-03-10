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
            "https://iss.moex.com/iss/engines/stock/markets/shares/boards/tqtd/securities.json", // etf-usd
            "https://iss.moex.com/iss/engines/stock/markets/index/boards/SNDX/securities.json", // index
            "https://iss.moex.com/iss/engines/stock/markets/index/boards/RTSI/securities.json", // index
            "https://iss.moex.com/iss/engines/currency/markets/index/securities.json", // currency
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
            }
        }

        echo("OK" . PHP_EOL);
    }
}
