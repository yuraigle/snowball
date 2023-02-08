<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function register(): Application|Factory|View
    {
        return view("auth.register", []);
    }

    public function registerPost(Request $req): JsonResponse
    {
        try {
            Validator::make($req->post(), [
                'name' => 'required|string|min:4|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:4',
            ])->validate();
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 400);
        }

        $user = User::create([
            'name' => $req->post('name'),
            'email' => $req->post('email'),
            'password' => Hash::make($req->post('password')),
        ]);

        Auth::login($user);
        return response()->json(["OK"]);
    }

    public function login(): Application|Factory|View
    {
        return view("auth.login", []);
    }

    public function loginPost(Request $req): JsonResponse
    {
        try {
            Validator::make($req->post(), [
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:4',
            ])->validate();
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 400);
        }

        if (!Auth::attempt($req->only(['email', 'password']))) {
            return response()->json(["Неверный пароль или имя пользователя"], 401);
        }

        return response()->json(["OK"]);
    }

    public function logout(): Redirector|Application|RedirectResponse
    {
        Auth::logout();
        return redirect('/');
    }
}
