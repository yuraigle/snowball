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
}
