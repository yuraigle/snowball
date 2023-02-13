<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
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
    ifnull(uc.name, a.name) as name,
    a.ticker,
    a.icon,
    uc.target_weight
from `user_categories` uc
    left join `assets` a on a.id = uc.asset_id
where uc.`user_id` = ?
group by uc.id, uc.parent_id, uc.name, uc.target_weight, a.name, a.ticker, a.icon
order by uc.parent_id, uc.target_weight desc

", [Auth::id()]);

        return view("categories.index", compact('stats'));
    }
}
