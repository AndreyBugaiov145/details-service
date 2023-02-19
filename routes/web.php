<?php

use App\Http\Controllers\ParsingSettingController;
use App\Http\Controllers\Users;
use App\Models\Detail;
use App\Models\ParsingSetting;
use App\Services\GrabberService;
use App\Services\ParserService;
use App\Services\ProxyScrape;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use PHPHtmlParser\Dom;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;

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

Route::get('/test2', function () {
    $options = [
        "timeout" => 1000,
        "protocol" => "http",
        "country" => "all",
        "ssl" => "all",
        "anonymity" => "all"
    ];

    $endpoint = new ProxyScrape($options);
    $list = $endpoint->get();
    dd($list);
});
Route::get('/test', function () {
    $start = microtime(true);
    $detailService = new \App\Services\DetailService();
    $detailService->fetchDetailsInfo();
    echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
    dd(1);

//    $httpClient = new \GuzzleHttp\Client();
//    $response = $httpClient->get('https://rud.ua/ru/consumer/recipe/',['timeout' => 15,'proxy' => '68.183.103.250:3128']
//    );
////    $html= (string)$response->getBody();
//    dd($response);
//    $request = new Request('GET', 'https://www.russianfood.com/');

    $client = new \GuzzleHttp\Client();
    $promises = [];
    for ($i = 1; $i <= 3; $i++) {
//        $httpClient = new \GuzzleHttp\Client();
//    $response = $httpClient->get('https://www.russianfood.com/');
        $url = 'https://povar.ru/';
        if ($i  == 2) {
            $url = 'https://www.rockauto.com/';
        }
        $name = 'test'. $i;
        $promises[$name] = $client->getAsync($url,['timeout' => 20,'proxy' => '135.125.113.41:3128']);
    }
//    echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
//    dd(1);

//    $promises = [
//        'test' => $client->getAsync('https://www.russianfood.com/'),
//        'test2'   => $client->getAsync('https://www.russianfood.com/'),
//    ];
//    $request = $client->getAsync('https://habr.com');
//    $request = $client->getAsync('https://habr.com');
    try {
//        $response = $client->send($request, ['timeout' => 0.5]);
//        $results = Promise\unwrap($promises);

// когда все запросы завершатся , даже если были ошибки
        $promise =  Promise\settle($promises);
        $results = $promise->wait();
//        $results = $promise->then(function (ResponseInterface $res) {
//            dd(1);
//            dump($res) ;
//        },
//            function (RequestException $e) {
//                dd(2);
//                dump( $e->getMessage()) ;
//                dump( $e->getRequest()->getMethod());
//            });
        echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
        dd($results);
    }catch (\GuzzleHttp\Exception\ConnectException $e){
dd(1);
    }



    dd((string)$response->getBody());
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

