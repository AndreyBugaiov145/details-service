<?php

use App\Http\Controllers\ParsingSettingController;
use App\Http\Controllers\Users;
use App\Models\Detail;
use App\Models\ParsingSetting;
use App\Services\GrabberService;
use App\Services\ParserService;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Http;
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
    /*
      $html = file_get_contents('https://www.rockauto.com');
      echo $html;

       $dom = new Dom;
       $dom->loadStr($html);

       $a = $dom->find('div.ranavnode');


       $jsn = (array)json_decode(html_entity_decode($a[0]->firstChild()->getAttribute('value')));

       $jsn['href'] ='https://www.rockauto.com'.$a[0]->find('a.navlabellink')[0]->getAttribute('href');
   //    $jsn = (array)json_decode($jsn);
   //    $jsn = ".$a[0]->firstChild()->getAttribute('value').";
   //
   //    dd(html_entity_decode($jsn));
   //    dd( $jsn);
   //dd();
       dump($jsn);*/


//    $grabber = new GrabberService();
//    $httpClient = new \GuzzleHttp\Client();
//    $response = $httpClient->get('https://www.rockauto.com/en/catalog/audi,2020,a4+allroad,2.0l+l4+turbocharged,3445815,belt+drive,belt,8900');
//    $html= (string)$response->getBody();
//   $parserService = new ParserService($html);
//   dd($parserService->getDetails());
//   $rez  = $parserService->getAllChildCategoriesWithJns();
//   $Detail =  new \App\Models\Category(['title'=>'adsada']);
//    $Detail->save();

//   $rez =  \App\Models\Category::upsert(['title'=>'adsada','parent_id'=>1], ['title']);
//    dd($rez);

   $detailService = new \App\Services\DetailService();
    $detailService->fetchDetailsInfo();
    dd( 1);

//    $rez = pq($doc,'title');

//    dd($rez);
//    phpQuery::unloadDocuments($doc);

//    echo $html;

    return 1;
});


Route::get('/', function () {
    return view('home');
});

Route::prefix('login')->group(function () {
    Route::get('/', function () {
        return view('auth.sign-in');
    });
    Route::post('/', [Users::class, 'login'])->name('login');
});

Route::prefix('admin')->group(function () {
    Route::get('/', function () {
        return view('admin.home');
    })->middleware('auth')->name('admin');

    Route::get('/reset-password', function () {
        return view('admin.reset-password');
    })->middleware('auth')->name('reset-password-page');

    Route::post('/reset-password', [Users::class, 'changePassword'])->middleware('auth')->name('reset-password');

    Route::get('/parser-setting', function () {
        return view('admin.parser-setting');
    })->middleware('auth')->name('parser-setting');
    Route::resource('/settings', ParsingSettingController::class)->except(['show', 'edit', 'create'])->middleware('auth');
});

