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
        $tickers = ['VT', 'BTC-USD', 'ETH-USD', 'BNB-USD', 'MATIC-USD', 'XMR-USD', '^GSPC'];
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

            $s = $results[$ticker];

            if ($s['regularMarketPrice']) {
                DB::update("update `assets` set `price`=?, `updated_at` = now() where `id`=?",
                    [$s['regularMarketPrice'], $aid]);
            }
        }

        echo("OK" . PHP_EOL);
    }
}
