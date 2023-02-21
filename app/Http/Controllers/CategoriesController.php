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

class CategoriesController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index(): Factory|View|Application
    {
        $stats = DB::select("
select
    uc.id,
    uc.parent_id,
    ifnull(uc.name, a.name) as `name`,
    a.id as aid,
    a.ticker,
    uc.target_weight,
    ifnull(uc.ord, 0) as ord
from `user_categories` uc
    left join `assets` a on a.id = uc.asset_id
where uc.`user_id` = ?
group by uc.id, uc.parent_id, uc.name, uc.target_weight, uc.ord, a.id, a.name, a.ticker
", [Auth::id()]);

        return view("categories.index", compact('stats'));
    }

    public function update(Request $req): JsonResponse
    {
        $res = DB::select("select * from `user_categories` where `user_id` = ?", [Auth::id()]);

        $newUserCats = $req->post();
        foreach ($res as $uc) {
            $cc = array_filter($newUserCats, function ($c) use ($uc) {
                return !empty($c['id']) && $c['id'] == $uc->id;
            });
            $cc = !empty($cc) ? array_values($cc)[0] : null;
            if ($cc && $uc->asset_id) {
                // обновилась привязка к акции
                DB::update("update `user_categories` set `parent_id`=?, `target_weight`=?,
                             `ord`=? where `id`=?",
                    [$cc['parent_id'], $cc['target_weight'], $cc['ord'], $uc->id]);
            } elseif ($cc && !$uc->asset_id) {
                // обновилась привязка к категории
                DB::update("update `user_categories` set `parent_id`=?, `target_weight`=?,
                             `ord`=?, `name`=? where `id`=?",
                    [$cc['parent_id'], $cc['target_weight'], $cc['ord'], $cc['name'], $uc->id]);
            } elseif (!$cc) {
                // удалилась привязка
                DB::delete("delete from `user_categories` where `id` = ?", [$uc->id]);
            }
        }

        // добавилась новая привязка
        foreach ($newUserCats as $cc) {
            if (empty($cc['id'])) {
                $assetId = $catName = null;
                if (preg_match('|^[A-Z]{1,5}$|', $cc['name'])) {
                    $assets = DB::select("select `id` from `assets` where `ticker` = ?", [$cc['name']]);
                    if (!empty($assets)) {
                        $assetId = $assets[0]->id;
                    }
                }

                if (!$assetId) {
                    $catName = $cc['name'];
                }

                DB::insert("insert into `user_categories` (`user_id`, `parent_id`, `asset_id`,
                               `name`, `target_weight`, `ord`) values (?,?,?,?,?,?)",
                    [Auth::id(), $cc['parent_id'], $assetId, $catName, $cc['target_weight'], $cc['ord']]);
            }
        }

        return response()->json(["OK"]);
    }
}
