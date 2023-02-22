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
    sum(`amount`) as cnt,
    sum(`amount` * uh.price) as ttl_spent,
    sum(`amount`) * max(a.price) as ttl_now,
    ifnull(uc.ord, 0) as ord
from `user_categories` uc
    left join `assets` a on a.id = uc.asset_id
    left join `user_holdings` uh on uh.user_id = uc.user_id and uh.asset_id = uc.asset_id
where uc.`user_id` = ?
group by uc.id, uc.parent_id, uc.name, uc.target_weight, uc.ord, uc.color, a.name, a.ticker, a.price
", [Auth::id()]);

        foreach ($stats as $row) {
            $row->ttl_spent = $this->calcTotalSpent($stats, $row->id);
            $row->ttl_now = $this->calcTotalNow($stats, $row->id);
        }

        return view("dashboard.index", compact('stats'));
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
select sum(`amount`) as cnt, sum(amount * h.price) as ttl_spent
from `user_holdings` h left join `assets` a on a.id = h.asset_id
where user_id = ? and asset_id = ?", [Auth::id(), $asset->id]);

        return view("dashboard.asset", [
            'asset' => $asset,
            'transactions' => $tx,
            'stats' => $stats[0],
        ]);
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
