<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssetYfapiUpdateCommand extends Command
{
    protected $signature = 'assets:yfapi';
    protected $description = 'Update current price data';

    public function handle()
    {
        $tickers = ['VT', 'BTC-USD', 'ETH-USD', 'BNB-USD', 'MATIC-USD', 'XMR-USD', '^GSPC', '83010.HK'];
        $url = 'https://yfapi.net/v6/finance/quote?region=US&lang=en&symbols=' . urlencode(join(',', $tickers));

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

        $results = [];
        $json = json_decode($resp, true);

        foreach ($json["quoteResponse"]["result"] as $s) {
            $ticker = preg_replace("|-USD$|", "", $s['symbol']);

            if (str_contains($ticker, "GSPC")) {
                $ticker = "SP500";
            }

            $results[$ticker] = $s;
        }

        $tickers = DB::select("select id, ticker from `assets`");
        foreach ($tickers as $row1) {
            $ticker = $row1->ticker;
            $aid = $row1->id;

            if (!isset($results[$ticker])) {
                continue;
            }

//            $s = $results[$ticker];
            $price = $results[$ticker]['regularMarketPrice'];

            if ($price) {
                DB::update("update `assets` set `price`=?, `updated_at` = now() where `id`=?",
                    [$price, $aid]);

                $date = date('Y-m-d');
                $exists = DB::selectOne("select count(*) as c from `asset_history` where `asset_id` = ? and `date` = ?",
                    [$aid, $date]);

                if ($exists->c) {
                    DB::update("update `asset_history` set `close`=?, `updated_at` = now()
                            where `date`=? and `asset_id`=?", [$price, $date, $aid]);
                } else {
                    DB::insert("insert into `asset_history` (`asset_id`, `close`, `date`)
                            values (?, ?, ?)", [$aid, $price, $date]);
                }
            }
        }

        echo("OK" . PHP_EOL);
    }
}
