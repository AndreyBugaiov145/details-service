<?php

use App\Http\Controllers\ParsingSettingController;
use App\Http\Controllers\Users;
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

Route::get('/', function () {
    return view('home');
});

Route::prefix('login')->group(function (){
    Route::get('/', function () {
        return view('auth.sign-in');
    });
    Route::post('/', [Users::class, 'login'])->name('login');
});

Route::prefix('admin')->group(function (){
    Route::get('/', function () {
        return view('auth.sign-in');
    })/*->middleware('auth')*/;

    Route::get('/parser-setting',  function () {
        return view('admin.parser-setting');
    })/*->middleware('auth')*/;
    Route::resource('/settings', ParsingSettingController::class)->except(['show','edit','create'])/*->middleware('auth')*/;
});

