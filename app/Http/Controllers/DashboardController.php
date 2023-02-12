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
    a.price,
    a.icon,
    sum(`amount`) as cnt,
    sum(`amount` * uh.price) as ttl_spent,
    sum(`amount`) * max(a.price) as ttl_now,
    sum(`amount` * uh.price) / sum(`amount`) as average
from `user_categories` uc
    left join `assets` a on a.id = uc.asset_id
    left join `user_holdings` uh on uh.user_id = uc.user_id and uh.asset_id = uc.asset_id
where uc.`user_id` = ?
group by uc.id, uc.parent_id, uc.name, uc.target_weight, a.name, a.ticker, a.price, a.icon
order by uc.parent_id, uc.target_weight desc
", [Auth::id()]);

        $categoriesTotal = [];

        foreach ($stats as $stat) {
            if ($stat->parent_id) {
                if (!isset($categoriesTotal[$stat->parent_id])) {
                    $categoriesTotal[$stat->parent_id] = ['ttl_now' => 0, 'ttl_spent' => 0];
                }
                $categoriesTotal[$stat->parent_id]['ttl_now'] += $stat->ttl_now;
                $categoriesTotal[$stat->parent_id]['ttl_spent'] += $stat->ttl_spent;
            }
        }

        foreach ($stats as $stat) {
            if (!$stat->ttl_spent && !empty($categoriesTotal[$stat->id])) {
                $stat->ttl_spent = $categoriesTotal[$stat->id]['ttl_spent'];
                $stat->ttl_now = $categoriesTotal[$stat->id]['ttl_now'];
            }
        }

        // TODO пересчёт не будет работать при вложенности >1

        return view("dashboard.index", compact('stats'));
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
