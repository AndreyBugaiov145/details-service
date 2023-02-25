<?php

use App\Http\Controllers\ParsingSettingController;
use App\Http\Controllers\Users;
use App\Models\Detail;
use App\Models\ParsingSetting;
use App\Services\GrabberService;
use App\Services\ParserService;
use App\Services\ProxyScrape;
use App\Services\ProxyService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use PHPHtmlParser\Dom;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

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

Route::get('/test4', function () {
    $ProxyService = new ProxyService();
    $proxies = $ProxyService->fetchAndSaveProxies();
});

Route::get('/test3', function () {
    $sh = curl_init('http://www.rockauto.com/');
    curl_setopt($sh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($sh, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($sh, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36');
    curl_setopt($sh, CURLOPT_PROXY_SSL_VERIFYPEER, false);
    curl_setopt($sh, CURLOPT_PROXY_SSL_VERIFYHOST, false);

    curl_setopt($sh, CURLOPT_TIMEOUT, 20);
    curl_setopt($sh, CURLOPT_CONNECTTIMEOUT, 10);

    $rez = curl_exec($sh);
    curl_close($sh);
    dd($rez);

//    curl_setopt($sh,CURLOPT_PROXY);
//    curl_setopt($sh,CURLOPT_PROXYTYPE,CURLPROXY_HTTP);

});
Route::get('/test2', function () {
    $options = [
        "timeout" => 1000,
        "protocol" => "http",
        "country" => "all",
        "ssl" => "all",
        "anonymity" => "all"
    ];

    $endpoint = new ProxyScrape($options);
    $list1 = $endpoint->get();

    $endpoint = new ProxyScrape([
        "timeout" => 1000,
        "protocol" => "https",
        "country" => "all",
        "ssl" => "all",
        "anonymity" => "all"
    ]);
    $list2 = $endpoint->get();


    $client = new \GuzzleHttp\Client();
    $list3 = array_merge($list2, $list1);
    $promises = [];
    $onRedirect = function (
        RequestInterface  $request,
        ResponseInterface $response,
        UriInterface      $uri
    ) {
        echo 'Redirecting! ' . $request->getUri() . ' to ' . $uri . "\n";
    };
    foreach ($list3 as $key => $proxy) {
//        if ($key > 100) {
//            continue;
//        }

        $promises[$proxy] = $client->getAsync(
//            'https://developer.cms.gov/public-apis/documentation/httpbin',
            'https://www.rockauto.com/',
            [
                'timeout' => 20,
                'connect_timeout' => 10,
                'proxy' => $proxy,
//                'verify' => false,
                'headers' => [

                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
                ],
//                'allow_redirects' => [
//                    'max' => 10,        // allow at most 10 redirects.
//                    'strict' => true,      // use "strict" RFC compliant redirects.
//                    'referer' => true,      // add a Referer header
//                    'protocols' => ['https', 'http'], // only allow https URLs
//                    'on_redirect' => $onRedirect,
//                    'track_redirects' => true
//                ]
            ]);
    }
    try {
        $promise = Promise\settle($promises);
        $results = $promise->wait();

        $success = [];
        foreach ($results as $key => $r) {
            if ($r['state'] != 'rejected') {
                $success[$key] = $r;
            }
        }
        dump(count($list1));
        dump(count($success));
        dump($success);
        dd($results);
    } catch (\GuzzleHttp\Exception\ConnectException $e) {
        dd(1);
    }
    dd(0);


    $rez = [];
    foreach ($list1 as $item) {
        if (!in_array($item, array_values($list2))) {
            $rez[] = $item;
        }
    }

    dd($rez);

    foreach ($list1 as $key => $item) {
        echo $item . '</br>';

        if ($key === 99 || $key == 199 || $key == 299) {
            echo '</br></br></br>';
        }
    }

});
Route::get('/test', function () {
//    $start = microtime(true);
//    $detailService = new \App\Services\DetailService();
//    $detailService->fetchDetailsInfo();
//    echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
//    dd(1);

//    $httpClient = new \GuzzleHttp\Client();
//    $response = $httpClient->get('https://rud.ua/ru/consumer/recipe/',['timeout' => 15,'proxy' => '68.183.103.250:3128']
//    );
////    $html= (string)$response->getBody();
//    dd($response);
//    $request = new Request('GET', 'https://www.russianfood.com/');

    $client = new \GuzzleHttp\Client();
    try {
        $response = $client->get(
            'https://httpbin.org/anything',
//            'https://www.rockauto.com/',
            [
                'timeout' => 5,
                'proxy' => '198.57.27.6:8850'
            ]);
        dd($response->getOptions());
    } catch (Exception $e) {
        dd($e->getRequest()->getOptions());
    }

    dd((string)$response->getBody());
    $promises = [];
    for ($i = 1; $i <= 3; $i++) {
//        $httpClient = new \GuzzleHttp\Client();
//    $response = $httpClient->get('https://www.russianfood.com/');
        $url = 'https://povar.ru/';
        if ($i == 2) {
            $url = 'https://www.rockauto.com/';
        }
        $name = 'test' . $i;
        $promises[$name] = $client->getAsync($url, ['timeout' => 20, 'proxy' => '135.125.113.41:3128']);
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
        $promise = Promise\settle($promises);
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
        echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
        dd($results);
    } catch (\GuzzleHttp\Exception\ConnectException $e) {
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
    dd(1);

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

