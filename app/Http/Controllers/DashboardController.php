<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DashboardController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index(): Factory|View|Application
    {
        $stats = DB::select("
select
    uc.id,
    uc.parent_id,
    ifnull(uc.name, a.name) as name,
    a.ticker,
    uc.target_weight,
    uc.color,
    a.price,
    a.currency,
    sum(`amount`) as cnt,
    sum(`amount` * uh.price) * (case when a.currency = 'USD' then usd.price else 1 end) as ttl_spent,
    sum(`amount`) * max(a.price) * (case when a.currency = 'USD' then usd.price else 1 end) as ttl_now,
    (a.price - ah1.close) / ah1.close as 1D,
    (a.price - ah3.close) / ah3.close as 3D,
    (a.price - ah7.close) / ah7.close as 7D,
    (a.price - ah30.close) / ah30.close as 30D,
    ifnull(uc.ord, 0) as ord
from `user_categories` uc
    left join `assets` a on a.id = uc.asset_id
    left join `assets` usd on usd.ticker = 'USDFIX'
    left join `user_holdings` uh on uh.user_id = uc.user_id and uh.asset_id = uc.asset_id
    left join (
        select asset_id, close, row_number() over(partition by asset_id order by date desc) as d
        from asset_history where date <= date_sub(now(), interval 1 day)
    ) ah1 on ah1.asset_id = a.id and ah1.d = 1
    left join (
        select asset_id, close, row_number() over(partition by asset_id order by date desc) as d
        from asset_history where date <= date_sub(now(), interval 3 day)
    ) ah3 on ah3.asset_id = a.id and ah3.d = 1
    left join (
        select asset_id, close, row_number() over(partition by asset_id order by date desc) as d
        from asset_history where date <= date_sub(now(), interval 7 day)
    ) ah7 on ah7.asset_id = a.id and ah7.d = 1
    left join (
        select asset_id, close, row_number() over(partition by asset_id order by date desc) as d
        from asset_history where date <= date_sub(now(), interval 30 day)
    ) ah30 on ah30.asset_id = a.id and ah30.d = 1
where uc.`user_id` = ?
group by uc.id, uc.parent_id, uc.name, uc.target_weight, uc.ord, uc.color, a.name,
         a.ticker, a.price, a.currency, usd.price
", [Auth::id()]);

        foreach ($stats as $row) {
            $row->ttl_spent = $this->calcTotalSpent($stats, $row->id);
            $row->ttl_now = $this->calcTotalNow($stats, $row->id);
        }

        $indexes = [];
        $rows = DB::select("select ticker, price from assets where ticker in ('USDFIX', 'IMOEX', 'RTSI', 'BTC')");
        foreach ($rows as $row) {
            $indexes[$row->ticker] = floatval($row->price);
        }

        return view("dashboard.index", compact('stats', 'indexes'));
    }

    private function calcTotalSpent($stats, $id): float
    {
        $totalSpent = 0;

        foreach ($stats as $row) {
            if ($row->id == $id && $row->ticker) {
                $totalSpent += $row->ttl_spent ?: 0;
            }
            if ($row->parent_id == $id) {
                $totalSpent += $this->calcTotalSpent($stats, $row->id) ?: 0;
            }
        }

        return $totalSpent;
    }

    private function calcTotalNow($stats, $id): float
    {
        $totalNow = 0;

        foreach ($stats as $row) {
            if ($row->id == $id && $row->ticker) {
                $totalNow += $row->ttl_now ?: 0;
            }
            if ($row->parent_id == $id) {
                $totalNow += $this->calcTotalNow($stats, $row->id) ?: 0;
            }
        }

        return $totalNow;
    }

    public function asset($ticker): Factory|View|Application
    {
        $assets = DB::select("select * from `assets` where `ticker` = ?", [$ticker]);

        if (count($assets) == 1) {
            $asset = $assets[0];
        } else {
            abort(404);
        }

        $tx = DB::select("select * from `user_holdings` where `user_id`=? and `asset_id`=? order by deal_date desc",
            [Auth::id(), $asset->id]);

        $stats = DB::select("
select sum(uh.`amount`) as cnt,
       sum(uh.`amount` * uh.price) as ttl_spent,
       sum(uh.`amount` * uh.price) * (case when a.currency = 'USD' then usd.price else 1 end) as ttl_spent_rub
from `user_holdings` uh
    left join `assets` a on a.id = uh.asset_id
    left join `assets` usd on usd.ticker = 'USDFIX'
where user_id = ? and asset_id = ?
group by usd.price, a.currency
", [Auth::id(), $asset->id]);

        return view("dashboard.asset", [
            'asset' => $asset,
            'transactions' => $tx,
            'stats' => !empty($stats) ? $stats[0] : null,
            'ttlByUser' => $this->sumByUser(Auth::id()),
            'ttlByUserAsset' => $this->sumByUserAsset(Auth::id(), $asset->id)
        ]);
    }

    private function sumByUser($uid): float
    {
        $r = DB::select("select sum(a.price * uh.amount * case when a.currency = 'USD' then usd.price else 1 end) as ttl
            from user_holdings uh
                left join `assets` a on a.id = uh.asset_id
                left join `assets` usd on usd.ticker = 'USDFIX'
            where uh.user_id = ?
            ", [$uid]);
        return !empty($r) && $r[0]->ttl ? $r[0]->ttl : 0;
    }

    private function sumByUserAsset($uid, $aid): float
    {
        $r = DB::select("select sum(a.price * uh.amount * case when a.currency = 'USD' then usd.price else 1 end) as ttl
            from user_holdings uh
                left join `assets` a on a.id = uh.asset_id
                left join `assets` usd on usd.ticker = 'USDFIX'
            where uh.user_id = ? and a.id = ?
            ", [$uid, $aid]);
        return !empty($r) && $r[0]->ttl ? $r[0]->ttl : 0;
    }

    public function transaction(Request $req): JsonResponse
    {
        try {
            Validator::make($req->post(), [
                'asset_id' => 'required',
                'deal_type' => 'required',
                'deal_date' => 'required',
                'amount' => 'required',
                'price' => 'required',
                'currency' => 'required|string|max:3',
                'commission' => '',
            ])->validate();
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 400);
        }

        $id = $req->post('id');
        $uid = Auth::id();
        $aid = $req->post('asset_id');
        $date = $req->post('deal_date');
        $type = $req->post('deal_type', 0);
        $amount = floatval($req->post('amount'));
        $price = floatval($req->post('price'));
        $currency = $req->post('currency', 'USD');
        $commission = $req->post('commission', 0);
        if ($type == 1) {
            $amount *= -1;
        }

        if ($id) {
            DB::update("update `user_holdings` set deal_date=?, deal_type=?, amount=?, price=?,
                             currency=?, commission=? where id=?",
                [$date, $type, $amount, $price, $currency, $commission, $id]);
        } else {
            DB::insert("insert into `user_holdings` (user_id, asset_id, deal_type, deal_date, amount, price,
                             currency, commission) values (?,?,?,?,?,?,?,?)",
                [$uid, $aid, $type, $date, $amount, $price, $currency, $commission]);
        }

        return response()->json(["OK"]);
    }

}
