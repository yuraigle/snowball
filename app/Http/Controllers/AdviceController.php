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

class AdviceController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index(Request $req): Factory|View|Application
    {

        $stats = DB::select("
select
    uc.id,
    uc.parent_id,
    ifnull(uc.name, a.name) as name,
    a.id as aid,
    a.ticker,
    a.price,
    a.currency,
    a.lot,
    uc.ord,
    uc.target_weight / 100 as target_weight,
    uc.target_weight / 100 as calc_target_weight,
    (a.price - ah1.close) / ah1.close as 1D,
    (a.price - ah3.close) / ah3.close as 3D,
    (a.price - ah7.close) / ah7.close as 7D,
    (a.price - ah30.close) / ah30.close as 30D,
    uc.locked,
    sum(`amount`) * max(a.price) * (case when a.currency = 'USD' then usd.price else 1 end) as ttl_now
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
group by uc.id, uc.parent_id, uc.name, uc.target_weight, uc.ord, uc.locked, a.name, a.id,
         a.lot, a.ticker, a.price, a.currency, usd.price
order by isnull(uc.parent_id) desc, isnull(a.ticker) desc, a.ticker
", [Auth::id()]);

        $totalSum = 0;
        foreach ($stats as $row) {
            if ($row->aid) {
                $totalSum += $row->ttl_now;
            }
        }

        $add = $req->get("x", 15000);
        $nextSum = $totalSum + $add;

        // calc_target_weight - целевой вес для каждого актива
        foreach ($stats as $s) {
            $s->level = 0;
            if ($s->parent_id) {
                $parentId = $s->parent_id;
                while ($parentId != null) {
                    $s->level++;
                    $parent = $this->getStatById($stats, $parentId);
                    $parentId = $parent->parent_id;
                    $s->calc_target_weight *= $parent->target_weight;
                }
            }
        }

        // sum_to_add - сколько надо добавить до целевого распределения
        foreach ($stats as $s) {
            $s->ttl_now = $this->calcTotalNow($stats, $s->id);
            $s->current_weight = $s->ttl_now / $totalSum * 100;
            $s->sum_to_add = $s->calc_target_weight * $nextSum - $s->ttl_now;
            $s->sum2_to_add = 0;
            if ($s->locked) {
                $s->sum_to_add = 0;
            }
        }

        // sum2_to_add - сколько добавить из 100р
        $root = array_filter($stats, function ($s) {
            return !$s->parent_id;
        });

        $x = 0;
        foreach ($root as $s) {
            if ($s->sum_to_add > 0) {
                $x += $s->sum_to_add;
            }
        }
        foreach ($root as $s) {
            if ($s->sum_to_add > 0) {
                $s->sum2_to_add = $s->sum_to_add / $x * $add;
                $s->parent_name = "";
            }
        }

        for ($i = 0; $i < 10; $i++) {
            $filtered = array_filter($stats, function ($s) use ($i) {
                return $s->level == $i;
            });

            foreach ($filtered as $s) {
                $children = array_filter($stats, function ($s1) use ($s) {
                    return $s1->parent_id == $s->id;
                });

                $x = 0;
                foreach ($children as $child) {
                    $child->parent_name = $s->name;
                    if ($s->sum2_to_add <= 0) {
                        $child->sum2_to_add = 0;
                    } elseif ($child->sum_to_add > 0) {
                        $x += $child->sum_to_add;
                    }
                }

                foreach ($children as $child) {
                    if ($child->sum_to_add > 0 && $s->sum2_to_add > 0) {
                        $child->sum2_to_add = $child->sum_to_add / $x * $s->sum2_to_add;
                    } else {
                        $child->sum2_to_add = 0;
                    }
                }
            }
        }

        $stats = array_filter($stats, function ($s) {
            return $s->ticker && $s->sum2_to_add > 0;
        });

        usort($stats, function ($a, $b) {
            return
                [-$a->sum2_to_add]
                <=>
                [-$b->sum2_to_add];
        });

        $rest = $add;
        foreach ($stats as $s) {
            $s->toBuy = 0;
            if ($s->price * $s->lot <= $rest) {
                $s->toBuy = floor(min($s->sum2_to_add, $rest) / ($s->price * $s->lot));
                if ($s->toBuy < 1) {
                    $s->toBuy = 1;
                }
            }
            $rest -= $s->toBuy * $s->price * $s->lot;
        }

        usort($stats, function ($a, $b) {
            return
                [-$a->price * $b->lot]
                <=>
                [-$b->price * $b->lot];
        });

        foreach ($stats as $s) {
            if ($s->price * $s->lot <= $rest) {
                $toBuy = floor($rest / ($s->price * $s->lot));
                $rest -= $toBuy * $s->price * $s->lot;
                $s->toBuy += $toBuy;
            }
        }

        usort($stats, function ($a, $b) {
            return
                [-$a->sum2_to_add]
                <=>
                [-$b->sum2_to_add];
        });

        $sumToSpend = array_reduce($stats, function ($r, $s) {
            $r += $s->toBuy * $s->price * $s->lot;
            return $r;
        });

        return view("advice.index", compact('stats', 'add', 'sumToSpend'));
    }

    private function getStatById($stats, $id)
    {
        foreach ($stats as $stat) {
            if ($stat->id == $id) {
                return $stat;
            }
        }

        return null;
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

    public function ok(Request $req): JsonResponse
    {
        foreach ($req->post() as $s) {
            if ($s['toBuy'] > 0) {
                DB::insert("insert into `user_holdings` (user_id, asset_id, amount, price, currency)
                            values (?, ?, ?, ?, ?)",
                    [1, $s['aid'], floatval($s['toBuy']) * floatval($s['lot']), floatval($s['price']), 'RUB']);
            }
        }

        return response()->json("OK");
    }
}
