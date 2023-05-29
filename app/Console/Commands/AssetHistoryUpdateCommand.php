<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssetHistoryUpdateCommand extends Command
{
    protected $signature = 'assets:history';
    protected $description = 'Update history price data';

    public function handle()
    {
        $results = [];
        $links = [
            "https://iss.moex.com/iss/history/engines/stock/markets/shares/boards/tqbr/securities.json",
            "https://iss.moex.com/iss/history/engines/stock/markets/shares/boards/tqbr/securities.json?start=100",
            "https://iss.moex.com/iss/history/engines/stock/markets/shares/boards/tqbr/securities.json?start=200",
            "https://iss.moex.com/iss/history/engines/stock/markets/shares/boards/tqtf/securities.json",
            "https://iss.moex.com/iss/history/engines/stock/markets/shares/boards/tqtf/securities.json?start=100",
            "https://iss.moex.com/iss/history/engines/currency/markets/index/securities.json",
        ];

        foreach ($links as $link) {
            $resp = json_decode(file_get_contents($link), true);
            if (!$resp || empty($resp['history']) || empty($resp['history']['data'])) {
                continue;
            }

            $cols = $resp['history']['columns'];
            $cols = array_flip($cols);
            $priceCol = $cols['LEGALCLOSEPRICE'] ?? $cols['CLOSE'];

            foreach ($resp['history']['data'] as $row) {
                $ticker = $row[$cols['SECID']];
                $date = $row[$cols['TRADEDATE']];
                $open = $row[$cols['OPEN']];
                $high = $row[$cols['HIGH']];
                $low = $row[$cols['LOW']];
                $close = $row[$priceCol];
                $results[$ticker] = [$open, $high, $low, $close, $date];
            }
        }

        $tickers = DB::select("select id, ticker from `assets`");
        foreach ($tickers as $row1) {
            $ticker = $row1->ticker;
            $aid = $row1->id;

            if (isset($results[$ticker])) {
                $exists = DB::selectOne("select count(*) as c from `asset_history` where `asset_id` = ? and `date` = ?",
                    [$aid, $results[$ticker][4]]);

                if ($exists->c) {
                    DB::update("update `asset_history` set `open`=?, `high`=?, `low`=?, `close`=?
                            where `date`=? and `asset_id`=?", [...$results[$ticker], $aid]);
                } else {
                    DB::insert("insert into `asset_history` (`asset_id`, `open`, `high`, `low`, `close`, `date`)
                            values (?, ?, ?, ?, ?, ?)", [$aid, ...$results[$ticker]]);
                }
            }
        }

        echo("OK" . PHP_EOL);
    }
}
