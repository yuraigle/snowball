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

        $curr = $this->getCurrentDataMoex("tqbr", $tickers); // moex-stocks
        $hist = $this->getHistoryDataMoex("tqbr", $tickers);

        $curr = array_merge($curr, $this->getCurrentDataMoex("tqtf", $tickers)); // moex-etf
        $hist = array_merge($hist, $this->getHistoryDataMoex("tqtf", $tickers));

        $curr = array_merge($curr, $this->getCurrentDataMoex("tqtd", $tickers)); // moex-etf-usd
        $hist = array_merge($hist, $this->getHistoryDataMoex("tqtd", $tickers));

        $curr = array_merge($curr, $this->getCurrencyDataMoex());

        $curr = array_merge($curr, $this->getCurrentDataYahoo('VT'));
        $curr = array_merge($curr, $this->getCurrentDataYahoo('BTC-USD'));
        $curr = array_merge($curr, $this->getCurrentDataYahoo('ETH-USD'));
        $curr = array_merge($curr, $this->getCurrentDataYahoo('BNB-USD'));
        $curr = array_merge($curr, $this->getCurrentDataYahoo('MATIC-USD'));
        $curr = array_merge($curr, $this->getCurrentDataYahoo('XMR-USD'));

        foreach ($tickers as $ticker) {
            $price = null;
            if (isset($curr[$ticker])) {
                $price = $curr[$ticker];
            } elseif (isset($hist[$ticker])) {
                $price = $hist[$ticker];
            }

            if ($price) {
                DB::update("update `assets` set `price` = ?, `updated_at` = now() where `ticker` = ?",
                    [$price, $ticker]);
            }
        }

        print_r("ok" . PHP_EOL);
    }

    private function getCurrentDataMoex($board, $tickers): array
    {
        $str = file_get_contents("https://iss.moex.com/iss/" .
            "engines/stock/markets/shares/boards/$board/securities.json");
        $resp = json_decode($str, true);

        $result = [];
        if (!$resp || empty($resp['marketdata']) || empty($resp['marketdata']['data'])) {
            return $result;
        }

        foreach ($resp['marketdata']['data'] as $row) {
            $ticker = $row[0];
            $price = $row[4];

            if (in_array($ticker, $tickers) && $price) {
                $result[$ticker] = $price;
            }
        }

        return $result;
    }

    private function getCurrencyDataMoex(): array
    {
        $str = file_get_contents("https://iss.moex.com/iss/engines/currency/markets/index/securities.json");
        $resp = json_decode($str, true);

        $result = [];
        if (!$resp || empty($resp['marketdata']) || empty($resp['marketdata']['data'])) {
            return $result;
        }

        foreach ($resp['marketdata']['data'] as $row) {
            $ticker = $row[0];
            $price = $row[8];
            $result[$ticker] = $price;
        }

        return $result;
    }

    private function getHistoryDataMoex($board, $tickers): array
    {
        $result = [];
        $page = 0;

        while (true) {
            $str = file_get_contents("https://iss.moex.com/iss/" .
                "history/engines/stock/markets/shares/boards/$board/securities.json?start=$page");
            $resp = json_decode($str, true);

            if (!$resp || empty($resp['history']) || empty($resp['history']['data'])) {
                return $result;
            }

            foreach ($resp['history']['data'] as $row) {
                $ticker = $row[3];
                $price = $row[9];

                if (in_array($ticker, $tickers) && $price) {
                    $result[$ticker] = $price;
                }
            }

            if (count($resp['history']['data']) == 100) {
                $page += 100;
            } else {
                break;
            }
        }

        return $result;
    }

    private function getCurrentDataYahoo($ticker): array
    {
        $str = file_get_contents("https://query1.finance.yahoo.com/v8/finance/chart/$ticker");
        $resp = json_decode($str, true);

        $result = [];
        if (!$resp || !$resp['chart'] || !$resp['chart']['result']) {
            return $result;
        }

        $price = $resp['chart']['result'][0]['meta']['regularMarketPrice'];
        $ticker = preg_replace('|-USD|', '', $ticker);
        $result[$ticker] = $price;

        return $result;
    }
}
