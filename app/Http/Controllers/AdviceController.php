<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdviceController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index(Request $req): Factory|View|Application
    {
        $sql = <<<SQL
select
    uc.id,
    uc.parent_id,
    ifnull(uc.name, a.name) as name,
    a.ticker,
    a.price,
    sum(`amount`) * a.price * if(a.currency = 'USD', usd.price, 1) as ttl_now,
    uc.target_weight,
    uc.locked,
    (a.price - ah1.close) / ah1.close as 1D,
    (a.price - ah7.close) / ah7.close as 7D,
    (a.price - ah30.close) / ah30.close as 30D
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
        from asset_history where date <= date_sub(now(), interval 7 day)
    ) ah7 on ah7.asset_id = a.id and ah7.d = 1
    left join (
        select asset_id, close, row_number() over(partition by asset_id order by date desc) as d
        from asset_history where date <= date_sub(now(), interval 30 day)
    ) ah30 on ah30.asset_id = a.id and ah30.d = 1
where uc.`user_id` = ?
group by a.id, a.ticker, a.price, a.currency, usd.price, uc.id, a.name, uc.name,
         uc.parent_id, uc.target_weight, uc.locked
order by uc.parent_id, uc.target_weight desc
SQL;

        $stats = DB::select($sql, [Auth::id()]);

        foreach ($stats as $row) {
            $row->level = 0;
            $row->to_add = 0;
            $row->ttl_now = $this->calcTotalNow($stats, $row->id);
        }

        foreach ($stats as $row) {
            $sumInGroup = array_reduce($stats, function ($a, $b) use ($row) {
                return $a + ($b->parent_id == $row->parent_id ? $b->ttl_now : 0);
            });
            if ($sumInGroup) {
                $row->curr_weight = $row->ttl_now / $sumInGroup * 100;
            }

            if (!$row->parent_id) {
                $row->level = 1;
                $row->parent_id = 0;
                $row->parent_name = '';
            }

            if ($row->level) {
                $children = array_filter($stats, function ($a) use ($row) {
                    return $a->parent_id == $row->id;
                });

                array_map(function ($a) use ($row) {
                    $a->level = $row->level + 1;
                    $a->parent_name = $row->name;
                }, $children);
            }
        }

        $add = $req->query("x", 15000);

        $this->allocateChildren(0, $stats, $add);
        $this->allocateChildren(24, $stats);
        $this->allocateChildren(46, $stats);
        $this->allocateChildren(1, $stats);
        $this->allocateChildren(2, $stats);
        $this->allocateChildren(3, $stats);
        $this->allocateChildren(4, $stats);

        $stats = array_filter($stats, function ($row) {
            return $row->to_add > 0 && $row->ticker;
        });

        $s = array_reduce($stats, function ($a, $b) {
            return $a + $b->to_add;
        });
        array_map(function ($a) use ($s, $add) {
            $a->to_add = $a->to_add / $s * $add;
        }, $stats);

        usort($stats, function ($a, $b) {
            return
                [-$a->to_add]
                <=>
                [-$b->to_add];
        });

        return view("advice.index", compact('stats', 'add'));
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

    private function allocateChildren($parentId, $stats, $limit = 0)
    {
        if ($parentId) {
            $parent = array_filter($stats, function ($a) use ($parentId) {
                return $a->id == $parentId;
            });
            $limit = array_values($parent)[0]->to_add;
        }

        $children = array_filter($stats, function ($a) use ($parentId) {
            return $a->parent_id == $parentId;
        });

        $parentTtl = array_reduce($children, function ($a, $b) {
            return $a + $b->ttl_now;
        });
        $parentTtl += $limit;

        $hasUnderweight = 0;
        foreach ($children as $row) {
            $row->is_underweight = 0;

            if ($row->locked) {
                $row->to_add = 0;
            } else {
                if ($row->ttl_now / $parentTtl * 100 < $row->target_weight) {
                    $row->is_underweight = 1;
                    $hasUnderweight = 1;
                }

                $row->to_add = $row->target_weight / 100 * $parentTtl - $row->ttl_now;
            }
        }

        if ($hasUnderweight) {
            foreach ($children as $row) {
                if (!$row->is_underweight) {
                    $row->to_add = 0;
                }
            }
        }

        $sumToAllocate = array_reduce($children, function ($a, $b) {
            return $a + $b->to_add;
        });

        foreach ($children as $row) {
            if ($sumToAllocate) {
                $x = $row->to_add / $sumToAllocate * $limit;
                $row->to_add = $x > 100 ? $x : 0;
            }
        }
    }
}
