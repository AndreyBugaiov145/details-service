<?php

use App\Http\Controllers\Categories;
use App\Http\Controllers\Details;
use App\Http\Controllers\ParsingSettings;
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
})->name('home');

Route::prefix('login')->group(function () {
    Route::get('/', function () {
        return view('auth.sign-in');
    });
    Route::post('/', [Users::class, 'login'])->name('login');
});

Route::prefix('api')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('me', [Users::class, 'getMe'])->name('me');
    });

    Route::prefix('category')->group(function () {
        Route::get('/get-main', [Categories::class, 'getMainCategories']);
        Route::get('/children/{id}', [Categories::class, 'getChildrenCategories']);
        Route::get('/{id}', [Categories::class, 'getCategory']);
    });

    Route::prefix('detail')->group(function () {
        Route::get('/category-details/{category_id}', [Details::class, 'getCategoryDetails']);
        Route::get('/{id}/analogy-details', [Details::class, 'getAnalogyDetails']);
        Route::get('/{id}', [Details::class, 'getDetail']);
        Route::post('/', [Details::class, 'create'])->middleware('auth');
        Route::put('/{id}', [Details::class, 'update'])->middleware('auth');
        Route::delete('/{id}', [Details::class, 'delete'])->middleware('auth');
    });
});


Route::prefix('admin')->group(function () {
    Route::get('/', function () {
        return view('home');
    })->middleware('auth')->name('admin');

    Route::get('/logout', [Users::class, 'logout'])->middleware('auth')->name('logout');

    Route::get('/reset-password', function () {
        return view('admin.reset-password');
    })->middleware('auth')->name('reset-password-page');

    Route::post('/reset-password', [Users::class, 'changePassword'])->middleware('auth')->name('reset-password');

    Route::get('/parser-setting', function () {
        return view('admin.parser-setting');
    })->middleware('auth')->name('parser-setting');

    Route::resource('/settings', ParsingSettings::class)->except(['show', 'edit', 'create'])->middleware('auth');
    Route::get('/settings/{id}/update_category_parsing_status', [ParsingSettings::class, 'updateCategoryParsingStatus'])->middleware('auth');
    Route::get('/settings/{id}/update_detail_parsing_status', [ParsingSettings::class, 'updateDetailParsingStatus'])->middleware('auth');
});


//Route::get('/job', function () {
//    $JobsService = new JobsService();
//    $JobsService->createPendingCategoriesOrDetailsJobs();
//})->middleware('auth');
//
//Route::get('/pr', function () {
//    $JobsService = new ProxyService();
//    $r = $JobsService->getProxies();
//    dd($r);
//})->middleware('auth');

Route::get('test', function () {
    $existsDetails = DB::table('details')
        ->select('partkey', DB::raw('SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT analogy_details SEPARATOR "~~~"), "~~~", 1) AS analogy_details'))
        ->groupBy('partkey')
        ->whereIn('partkey',[1912204])
        ->get()->toArray();
dd(($existsDetails));
//    [{"id":1,"brand":"BMW","model":"1 SERIES M","years":"2011"},{"id":2,"brand":"BMW","model":"128I","years":"2008-2013"},{"id":3,"brand":"BMW","model":"135I","years":"2008-2012"},{"id":4,"brand":"BMW","model":"228I","years":"2014-2016"},{"id":5,"brand":"BMW","model":"230I","years":"2017-2021"},{"id":6,"brand":"BMW","model":"320I","years":"2014-2018"},{"id":7,"brand":"BMW","model":"328I","years":"2007-2016"},{"id":8,"brand":"BMW","model":"328XI","years":"2007-2008"},{"id":9,"brand":"BMW","model":"330I","years":"2017-2018"},{"id":10,"brand":"BMW","model":"335I","years":"2007-2015"},{"id":11,"brand":"BMW","model":"335IS","years":"2011-2012"},{"id":12,"brand":"BMW","model":"335XI","years":"2007-2008"},{"id":13,"brand":"BMW","model":"340I","years":"2016-2018"},{"id":14,"brand":"BMW","model":"428I","years":"2014-2016"},{"id":15,"brand":"BMW","model":"430I","years":"2017-2020"},{"id":16,"brand":"BMW","model":"435I","years":"2014-2016"},{"id":17,"brand":"BMW","model":"440I","years":"2017-2019"},{"id":18,"brand":"BMW","model":"528I","years":"2008-2010"},{"id":19,"brand":"BMW","model":"528XI","years":"2008"},{"id":20,"brand":"BMW","model":"535I","years":"2008-2016"},{"id":21,"brand":"BMW","model":"535XI","years":"2008"},{"id":22,"brand":"BMW","model":"550I","years":"2009-2012"},{"id":23,"brand":"BMW","model":"650I","years":"2007-2012"},{"id":24,"brand":"BMW","model":"M2","years":"2016-2021"},{"id":25,"brand":"BMW","model":"M240I","years":"2017-2021"},{"id":26,"brand":"BMW","model":"M3","years":"2008-2021"},{"id":27,"brand":"BMW","model":"M4","years":"2015-2021"},{"id":28,"brand":"BMW","model":"M5","years":"2013-2016"},{"id":29,"brand":"BMW","model":"M6","years":"2013-2018"},{"id":30,"brand":"BMW","model":"Z4","years":"2010-2016"}]
})->middleware('auth');
