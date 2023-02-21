<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssetsUpdateCommand extends Command
{
    protected $signature = 'assets:update';
    protected $description = 'Update prices';

    public function handle()
    {
        $tickers = [];
        $res1 = DB::select("select ticker from `assets`");
        foreach ($res1 as $row) {
            $tickers[] = $row->ticker;
        }

        $this->updateCurrentData($tickers);

        print_r("ok" . PHP_EOL);
    }

    private function updateCurrentData($tickers)
    {
        $str = file_get_contents("https://iss.moex.com/iss/" .
            "engines/stock/markets/shares/boards/tqbr/securities.json");
        $resp = json_decode($str, true);

        if (!$resp
            || empty($resp['marketdata'])
            || empty($resp['marketdata']['data'])
        ) {
            return;
        }

        foreach ($resp['marketdata']['data'] as $row) {
            $ticker = $row[0];
            $price = $row[4];

            if (in_array($ticker, $tickers)) {
                DB::update("update `assets` set `price` = ?, `updated_at` = now() where `ticker` = ?",
                    [$price, $ticker]);
            }
        }
    }

    private function updateHistoryData($tickers)
    {
        $pages = [0, 100, 200];
        foreach ($pages as $n) {
            $str = file_get_contents("https://iss.moex.com/iss/" .
                "history/engines/stock/markets/shares/boards/tqbr/securities.json?start=$n");
            $resp = json_decode($str, true);

            foreach ($resp['history']['data'] as $row) {
                $ticker = $row[3];
                $price = $row[9];

                if (in_array($ticker, $tickers)) {
                    DB::update("update `assets` set `price` = ?, `updated_at` = now() where `ticker` = ?",
                        [$price, $ticker]);
                }
            }
        }
    }
}
