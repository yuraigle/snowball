<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AssetCoinMarketUpdateCommand extends Command
{
    protected $signature = 'assets:cmc';
    protected $description = 'Update current price data CMC';

    public function handle(): void
    {
        $url = "https://coinmarketcap.com/";
        $coins = ['BTC', 'ETH', 'BNB', 'MATIC', 'XMR', 'TRX', 'AVAX', 'SOL'];

        $response = Http::get($url);
        if (!$response->ok()) {
            echo("ERROR" . PHP_EOL);
            return;
        }

        $body = $response->body();

        // 1..10
        $rx = '| data-sensors-click="true">([A-Z]{3,5})</p>.+?<span>\$([0-9.,]+)</span></a>|';
        preg_match_all($rx, $body, $m);
        foreach ($m[1] as $id => $ticker) {
            $price = floatval(preg_replace('/[^0-9.]/', '', $m[2][$id]));
            if (in_array($ticker, $coins)) {
                print_r($ticker . " " . $price . PHP_EOL);
                $this->updateTicker($ticker, $price);
            }
        }

        // 11..100
        $rx2 = '|<span class="crypto-symbol">([A-Z]{3,5})</span></a></td><td>\$<!-- -->([0-9.,]+)</td>|';
        preg_match_all($rx2, $body, $m);
        foreach ($m[1] as $id => $ticker) {
            $price = floatval(preg_replace('/[^0-9.]/', '', $m[2][$id]));
            if (in_array($ticker, $coins)) {
                print_r($ticker . " " . $price . PHP_EOL);
                $this->updateTicker($ticker, $price);
            }
        }

        echo("OK" . PHP_EOL);
    }

    private function updateTicker($ticker, $price) : void
    {
        $res = DB::select("select id from `assets` where ticker = ?", [$ticker]);
        $aid = $res[0]->id;

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
