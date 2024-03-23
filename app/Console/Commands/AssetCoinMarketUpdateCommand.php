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
        $url = "https://api.coinmarketcap.com/data-api/v3/cryptocurrency/listing?start=1&limit=100";
        $coins = ['BTC', 'ETH', 'BNB', 'MATIC', 'XMR', 'TRX', 'AVAX', 'SOL'];

        $response = Http::get($url);
        if (!$response->ok()) {
            echo("ERROR" . PHP_EOL);
            return;
        }

        $data = $response->json();
        foreach ($data['data']['cryptoCurrencyList'] as $c) {
            $ticker = $c['symbol'];
            if (in_array($ticker, $coins)) {
                $price = floatval($c['quotes'][0]['price']);
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
