<?php

use App\Http\Controllers\Categories;
use App\Http\Controllers\Details;
use App\Http\Controllers\ParsingSettings;
use App\Http\Controllers\Users;
use App\Repositories\CategoryRepository;
use App\Services\CurrencyService;
use App\Services\DetailService;
use App\Services\JobsService;
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


Route::get('/job', function () {
    $JobsService = new JobsService();
    $JobsService->createPendingCategoriesOrDetailsJobs();
});


Route::get('/test', function () {

//   $cat =  CategoryRepository::getLastChildrenCategories('CHEVROLET',2014);
//    $cat = collect($cat);
//    dd(\App\Models\Detail::whereIn('category_id',$cat->pluck('id'))->get()->count());
    dd(\App\Models\Detail::get()->count());
    $currencyService = new CurrencyService();
    $currencyService->updateUAHRate();
    dd(1);
  $d =  new DetailService(\App\Models\ParsingSetting::first());
  $d->fetchCategoriesAndDetailsInfo(12,25);
  dd(9999);
    class Foo
    {
        public $var = '3.14159265359';
    }

    $baseMemory = memory_get_usage();

    for ( $i = 0; $i <= 100000; $i++ )
    {
        $a = new Foo;
        $a->self = $a;
        if ( $i % 500 === 0 )
        {
            echo MemoryUtils::getUsedMemory(), "\n";
        }
    }
});
