<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssetYahooUpdateCommand extends Command
{
    protected $signature = 'assets:yahoo';
    protected $description = 'Update current price data';

    public function handle()
    {
        $tickers = ['VT', 'BTC-USD', 'ETH-USD', 'BNB-USD', 'MATIC-USD', 'XMR-USD'];
        $url = 'https://yfapi.net/v6/finance/quote?region=US&lang=en&symbols=' . join('%2C', $tickers);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "x-api-key: 0EkmiBCE877XGyk1mFtYS28nWZfVd9IXEHXoEm67",
            ],
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $resp = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
            return;
        }

        $results = [];
        $json = json_decode($resp, true);

        foreach ($json["quoteResponse"]["result"] as $s) {
            $ticker = preg_replace("|-USD$|", "", $s['symbol']);
            $results[$ticker] = $s;
        }

        $tickers = DB::select("select id, ticker from `assets`");
        foreach ($tickers as $row1) {
            $ticker = $row1->ticker;
            $aid = $row1->id;

            if (!isset($results[$ticker])) {
                continue;
            }

            $s = $results[$ticker];

            if ($s['regularMarketPrice']) {
                DB::update("update `assets` set `price`=?, `updated_at` = now() where `id`=?",
                    [$s['regularMarketPrice'], $aid]);

                $date = date("Y-m-d", $s["regularMarketTime"]);
                $dateMinus1 = date("Y-m-d", $s["regularMarketTime"] - 60 * 60 * 24);

                $exists = DB::selectOne("select count(*) as c from `asset_history` where `asset_id` = ? and `date` = ?",
                    [$aid, $date]);

                $ohlc = [
                    $s["regularMarketOpen"],
                    $s["regularMarketDayHigh"],
                    $s["regularMarketDayLow"],
                    $s["regularMarketPrice"],
                ];

                if ($exists->c) {
                    DB::update("update `asset_history` set `open`=?, `high`=?, `low`=?, `close`=?, updated_at=now()
                            where `date`=? and `asset_id`=?", [...$ohlc, $date, $aid]);
                } else {
                    DB::insert("insert into `asset_history` (`asset_id`, `open`, `high`, `low`, `close`, `date`)
                            values (?, ?, ?, ?, ?, ?)", [$aid, ...$ohlc, $date]);
                }

                DB::update("update `asset_history` set `close`=?, updated_at=now() where `date`=? and `asset_id`=?",
                    [$s["regularMarketPreviousClose"], $dateMinus1, $aid]);
            }
        }

        echo("OK" . PHP_EOL);
    }
}
