<?php

use App\Http\Controllers\AdviceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/welcome', [HomeController::class, 'welcome'])->name('welcome');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'loginPost']);
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'registerPost']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', [DashboardController::class, 'index'])->middleware('auth')->name('home');
Route::get('/asset/{ticker}', [DashboardController::class, 'asset'])->middleware('auth');
Route::post('/transaction', [DashboardController::class, 'transaction'])->middleware('auth');
Route::get('/categories', [CategoriesController::class, 'index'])->middleware('auth');
Route::post('/categories/update', [CategoriesController::class, 'update'])->middleware('auth');
Route::get('/advice', [AdviceController::class, 'index'])->middleware('auth');
