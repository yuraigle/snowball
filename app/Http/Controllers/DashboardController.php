<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DashboardController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index(): Factory|View|Application
    {
        $categories = DB::select("select * from `categories` where `user_id` = ? and `parent_id` is null", [1]);

        return view("dashboard.index", ['categories' => $categories]);
    }

    /**
     * @throws ValidationException
     */
    public function editCategory(Request $req): Redirector|Application|RedirectResponse
    {
        Validator::make($req->post('edit_cat'), [
            'name' => 'required|string|max:100',
            'target_weight' => 'required|numeric|between:0,100.00',
        ])->validate();

        return redirect('/dashboard');
    }
}
