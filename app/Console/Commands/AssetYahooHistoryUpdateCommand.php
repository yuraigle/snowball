<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssetYahooHistoryUpdateCommand extends Command
{
    protected $signature = 'assets:history-yahoo';
    protected $description = 'Update history price data from Yahoo Finance';

    public function handle() {
        $tickers = ['VT', 'BTC-USD', 'ETH-USD', 'BNB-USD', 'MATIC-USD', 'XMR-USD', '^GSPC'];

        foreach ($tickers as $symbol) {
            $ticker = preg_replace("|-USD$|", "", $symbol);
            if (str_contains($symbol, "GSPC")) {
                $ticker = "SP500";
            }

            $row1 = DB::selectOne("select id, ticker from `assets` where ticker = ?", [$ticker]);
            $aid = $row1->id;

            $url = "https://yfapi.net/v8/finance/chart/" . urlencode($symbol) . "?range=5d&interval=1d"; // 5d/1mo/6mo

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "x-api-key: " . env("YFAPI_KEY"),
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

            $json = json_decode($resp, true);

            $timestamps = $json["chart"]["result"][0]["timestamp"];
            for($i = 0; $i < count($timestamps); $i++) {
                $date = date("Y-m-d", $timestamps[$i]);

                $quote = $json["chart"]["result"][0]["indicators"]["quote"][0];
                $ohlc = [
                    $quote["open"][$i],
                    $quote["high"][$i],
                    $quote["low"][$i],
                    $quote["close"][$i],
                ];

                $exists = DB::selectOne("select count(*) as c from `asset_history` where `asset_id` = ? and `date` = ?",
                    [$aid, $date]);

                if ($exists->c) {
                    DB::update("update `asset_history` set `open`=?, `high`=?, `low`=?, `close`=?, updated_at=now()
                            where `date`=? and `asset_id`=?", [...$ohlc, $date, $aid]);
                } else {
                    DB::insert("insert into `asset_history` (`asset_id`, `open`, `high`, `low`, `close`, `date`)
                            values (?, ?, ?, ?, ?, ?)", [$aid, ...$ohlc, $date]);
                }
            }
        }

    }
}
