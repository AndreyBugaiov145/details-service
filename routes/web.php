<?php

use App\Http\Controllers\ParsingSettingController;
use App\Http\Controllers\Users;
use Illuminate\Support\Facades\Route;
use PHPHtmlParser\Dom;

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


Route::get('/test', function () {
    $html = file_get_contents('https://habr.com/ru/company/vk/blog/565028/');
    $dom = new Dom;
    $dom->loadStr($html);

    $a   = $dom->find('.tm-article-snippet__title.tm-article-snippet__title_h1');
    dd($a->firstChild()->text);
//    $rez = pq($doc,'title');

//    dd($rez);
//    phpQuery::unloadDocuments($doc);

    echo $html;

    return 1;
});


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
        return view('admin.home');
    })->middleware('auth')->name('admin');

    Route::get('/reset-password', function () {
        return view('admin.reset-password');
    })->middleware('auth')->name('reset-password-page');

    Route::post('/reset-password', [Users::class, 'changePassword'])->middleware('auth')->name('reset-password');

    Route::get('/parser-setting',  function () {
        return view('admin.parser-setting');
    })->middleware('auth')->name('parser-setting');
    Route::resource('/settings', ParsingSettingController::class)->except(['show','edit','create'])->middleware('auth');
});

