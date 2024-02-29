<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AssetYahooUpdateCommand extends Command
{
    protected $signature = 'assets:yahoo';
    protected $description = 'Update current price data';

    public function handle(): void
    {
        $tickers = ['^GSPC', '83010.HK', 'VT'];
        $results = [];

        foreach ($tickers as $ticker) {
            $response = Http::get('https://finance.yahoo.com/quote/' . $ticker);
            $rx = '| data-test="qsp-price" data-field="regularMarketPrice" [^>]+ value="([\d.]+)" |';

            if ($response->ok()) {
                $body = $response->body();
                if (preg_match($rx, $body, $m)) {
                    $key = $ticker;
                    if ($ticker === "^GSPC") {
                        $key = "SP500";
                    } elseif (str_contains($ticker, "-USD")) {
                        $key = str_replace('-USD', '', $ticker);
                    }

                    print_r($m[1] . PHP_EOL);
                    $results[$key] = floatval($m[1]);
                }
            }
        }

        $tickers = DB::select("select id, ticker from `assets`");
        foreach ($tickers as $row1) {
            $ticker = $row1->ticker;
            $aid = $row1->id;

            if (!isset($results[$ticker])) {
                continue;
            }

            $price = $results[$ticker];

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
