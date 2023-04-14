<?php

use App\Http\Controllers\Categories;
use App\Http\Controllers\Details;
use App\Http\Controllers\ParsingSettings;
use App\Http\Controllers\Users;
use App\Repositories\CategoryRepository;
use App\Services\CurrencyService;
use App\Services\DetailService;
use App\Services\JobsService;
use App\Services\ProxyScrape;
use App\Services\ProxyService;
use App\Utils\MemoryUtils;
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


Route::get('/job', function () {
    $JobsService = new JobsService();
    $JobsService->createPendingCategoriesOrDetailsJobs();
})->middleware('auth');

Route::get('/p', function () {
    $endpoint = new ProxyScrape([
        "timeout" => 8000,
        "protocol" => "all",
        "country" => "all",
        "ssl" => "all",
        "anonymity" => "all"
    ]);
    $proxies1 = $endpoint->get() ?: [];
    dd($proxies1);
})->middleware('auth');

Route::get('/pr', function () {
    $JobsService = new ProxyService();
    $proxies = $JobsService->getProxies();
//    $i = 0;
//    do {
//        $proxies = array_merge($proxies, $proxies);
//        $i++;
//    } while ($i < 6);
    dd($proxies);
})->middleware('auth');

Route::get('/md', function () {
    $categoriesDB = CategoryRepository::getLastChildrenCategories('BMW',2020);
    $categoriesDBIds = collect($categoriesDB)->pluck('id')->toArray();
    dd(count($categoriesDBIds));
    $categories =  \App\Models\Category::doesntHave('details')->whereIn('id',$categoriesDBIds)->get();
    dd($categories->toArray());
})->middleware('auth');


