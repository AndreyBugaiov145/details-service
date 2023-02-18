<?php

use App\Http\Controllers\ParsingSettingController;
use App\Http\Controllers\Users;
use App\Services\GrabberService;
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
    $grabber = new GrabberService();
    $href = 'https://www.rockauto.com/en/catalog/dodge,2022';
    $rez = $grabber->getChildCategories($href);
    echo $rez;
    dd(1);

    $client = new Client();

    $formData = [
        'payload' => json_encode([
            'partData' => [
                'listing_data_essential' => [
                    'partkey' => '11910309'
                ]
            ]
        ]),
        'func' => 'getbuyersguide',
        'api_json_request' => 1,
    ];

    $formData2 = [
        'payload' => json_encode([
            'jsn' => [
                'href' => 'https://www.rockauto.com/en/catalog/american+motors,1987,eagle,4.2l+258cid+l6,1003576,body+&+lamp+assembly'
            ]
        ]),
        'func' => 'navnode_fetch',
        'api_json_request' => 1,
    ];
    $url = 'https://www.rockauto.com/catalog/catalogapi.php';
    $url1 = 'https://www.rockauto.com/en/catalog/chevrolet,2022,aveo,1.5l+l4,3450509,engine,oil+filter,5340';
// RequestOptions::class;
    $response = $client->post( $url, [
        'form_params' => $formData2]);

    $htmlString = (string)$response->getBody();
    $obj = json_decode($htmlString, true);
    dd($obj);
    dd(1);

    if ($body['success'] === true && $body['data'] !== false) {
        dd($body['data']);
    }
    return 1;
    $html = file_get_contents('https://habr.com/ru/company/vk/blog/565028/');
    $dom = new Dom;
    $dom->loadStr($html);

    $a = $dom->find('.tm-article-snippet__title.tm-article-snippet__title_h1');
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

