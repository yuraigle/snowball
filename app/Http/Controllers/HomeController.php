<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class HomeController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function welcome(): Factory|View|Application
    {
        return view("home.welcome", []);
    }

    public function terms(): Factory|View|Application
    {
        return view("home.terms", []);
    }

    public function stats(Request $req): Response|Application|ResponseFactory
    {
        $tickers = $req->query('tickers', '');
        $tickers = explode(',', $tickers);

        $rows = DB::table("assets")
            ->select(['ticker', 'price', 'currency'])
            ->whereIn("ticker", $tickers)
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<ROWS>' . PHP_EOL;

        foreach ($tickers as $ticker) {
            foreach ($rows as $row) {
                if ($row->ticker == $ticker) {
                    $xml .= '  <ROW>';
                    $xml .= '<TICKER>' . $row->ticker . '</TICKER>';
                    $xml .= '<PRICE>' . (float) $row->price . '</PRICE>';
                    $xml .= '</ROW>' . PHP_EOL;
                }
            }
        }

        $xml .= '</ROWS>';

        return response($xml)
            ->header('Content-Type', 'text/xml');
    }

    public function delta7(Request $req): Response|Application|ResponseFactory
    {
        $tickers = $req->query('tickers', '');
        $tickers = explode(',', $tickers);

        $params = [];
        foreach ($tickers as $ticker) {
            $ticker = preg_replace('|[^A-Z0-9\.]|', '', $ticker);
            $params[] = "'" . $ticker . "'";
        }

        $rows = DB::select("
select a.ticker, round((a.price - ah7.close) / ah7.close, 4) as delta7
from `assets` a
    left join (
        select asset_id, close, row_number() over(partition by asset_id order by date desc) as d
        from asset_history where date <= date_sub(now(), interval 7 day)
    ) ah7 on ah7.asset_id = a.id and ah7.d = 1
where a.ticker in (" . join(',', $params) . ")
        ");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<ROWS>' . PHP_EOL;

        foreach ($tickers as $ticker) {
            foreach ($rows as $row) {
                if ($row->ticker == $ticker) {
                    $xml .= '  <ROW>';
                    $xml .= '<TICKER>' . $row->ticker . '</TICKER>';
                    $xml .= '<DELTA>' . (float) $row->delta7 . '</DELTA>';
                    $xml .= '</ROW>' . PHP_EOL;
                }
            }
        }

        $xml .= '</ROWS>';

        return response($xml)
            ->header('Content-Type', 'text/xml');
    }
}
