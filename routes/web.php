<?php

use App\Exceptions\GrabberException;
use App\Http\Controllers\ParsingSettingController;
use App\Http\Controllers\Users;
use App\Jobs\GrabbingDetails;
use App\Models\Category;
use App\Models\Detail;
use App\Models\DetailAnalogue;
use App\Models\ParsingSetting;
use App\Models\ParsingStatistic;
use App\Services\CategoryService;
use App\Services\CurrencyService;
use App\Services\FetchingService;
use App\Services\ParserService;
use App\Services\ProxyScrape;
use App\Services\ProxyService;
use App\Services\GrabberService;
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
use function App\Services\convert;

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
    Route::get('/settings/{id}/update_category_parsing_status', [ParsingSettingController::class, 'updateCategoryParsingStatus'])->middleware('auth');
    Route::get('/settings/{id}/update_detail_parsing_status', [ParsingSettingController::class, 'updateDetailParsingStatus'])->middleware('auth');
});













Route::get('/job', function () {

    ParsingStatistic::create([
        'parsing_setting_id' => 1,
        'parsing_status' => 'asdasd',
        'request_count' => 25,
        'request_time' => 25,
        'parsing_type' => 'ParsingStatistic::PARSING_CATEGORY'
    ]);
dd(1);
   $CurrencyService =  new CurrencyService();
    $CurrencyService->updateUAHRate();
dd(1);
    $client = new \GuzzleHttp\Client();
    $rq1 = $client->get(
        'https://api.apilayer.com/fixer/latest?base=USD&symbols=UAH',
        [
            'timeout' => 30,
            'connect_timeout' => 15,
            'headers' => [
                'apikey' => '3nnaqfJfoClzFm962FaLJ6hxZ0Bs64iO',
            ],
        ]);

    $rez = (string) $rq1->getBody();
    dd($rez);
});

Route::get('/parse', function () {

    function convert2($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    dump(convert2(memory_get_usage(true)));
    $start = microtime(true);
    $QueueService = new GrabberService();
    $QueueService->grabbingDetails();
    dump(23);
    echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
    dump(convert2(memory_get_usage(true)));
    dd(1);


});

Route::get('/test4', function () {
    $start = microtime(true);

    $request = new Request('PUT', 'http://httpbin.org/put');
    $request2 = new Request('PUT', 'http://httpbin.org/ip');
    $client = new \GuzzleHttp\Client();
    $pr = new ProxyService();
    $rez = [];
    $r = $pr->getProxies();
    dd($r);
//    for ($i = 0; $i < 0; $i++) {
//        $r = $pr->getProxies();
//        echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
//        $rez = array_merge($rez, $r);
//    }
//    $r = $pr->getProxies();
//    echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
//    $r2 = $pr->getProxies();
//    echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
//    $r3 = $pr->getProxies();
//    echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
//    $r4 = $pr->getProxies();
//    echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
//    $r5 = $pr->getProxies();
//    echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
//    dd(array_merge($r, $r2,$r3, $r4, $r5));
    $rq1 = $client->getAsync(
        'https://www.rockauto.com/',
        [
            'timeout' => 30,
            'connect_timeout' => 15,
            'proxy' => '183.172.208.225:7891',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
            ],
        ]);
    $rq4 = $client->getAsync(
        'https://www.rockauto.com/',
        [
            'timeout' => 30,
            'connect_timeout' => 15,
            'proxy' => '183.172.208.225:7891',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
            ],
        ]);


    $promises = [
        '183.172.208.225:7891',
        '183.172.208.225:7891',
        '183.172.208.225:7891'
    ];

    $proxiesChunks = array_chunk($promises, 1);
    foreach ($proxiesChunks as $proxies) {
        $promises = [];
        foreach ($proxies as $proxy) {
            $promises[] = $client->getAsync(
                'https://www.rockauto.com/',
                [
                    'timeout' => 30,
                    'connect_timeout' => 15,
                    'proxy' => $proxy,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
                    ],
                ]);;
        }

        $promise = Promise\settle($promises);
        $result = $promise->wait();
        dump($result);
    }


//    function fetch1(array $promisesChunks , $rez = null){
//        $client = new \GuzzleHttp\Client();
//        $rez = [];
//        $proxys = array_shift($promisesChunks);
//        foreach($proxys as $proxy){
//            $promises =   $client->getAsync(
//            'https://www.rockauto.com/',
//            [
//                'timeout' => 30,
//                'connect_timeout' => 15,
//                'proxy' => $proxy,
//                'headers' => [
//                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
//                ],
//            ]);;
//        }
//        $promise = Promise\settle($promises);
//        $result = $promise->wait();
//        $rez[] = $result;
//        echo 'Время выполнения ';
//        dump($rez);
//        if (count($promisesChunks) > 0) {
//            $rez[] = fetch1($promisesChunks,$result);
//        }
//        return $rez;
//    }
//
//
//
//    fetch1($promisesChunks);


    echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';

    dd(1);
    $start = microtime(true);
    $proxies = $pr->getProxies();

    dump($proxies);
    $client = new \GuzzleHttp\Client();
    foreach ($proxies as $key => $proxy) {
        $promises[$key] = $client->getAsync(
            'https://www.rockauto.com/',
            [
                'timeout' => 30,
                'connect_timeout' => 15,
                'proxy' => $proxy,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
                ],
            ]);
    }
    try {
//        $promise = Promise\settle($promises);
//        $results = $promise->wait();
        $success = [];
        $times = [];
        $chunks = array_chunk($promises, 15, true);
////        dd(1);
//        dump('geted proxies');
//        $start = microtime(true);
//        function gen_one_to_three($chunks ,$start,&$times) {
//            foreach ($chunks as $chunk) {
//                dump('$chunk');
//                // Обратите внимание, что $i сохраняет своё значение между вызовами.
//                $times[] = round(microtime(true) - $start, 4) ;
//                $promise = Promise\settle($chunk);
//                yield $rez =$promise->wait();
//
//            }
//        }
//
//        $generator = gen_one_to_three($chunks,$start,$times);
//        foreach ($generator as $value) {
//             dump($value);
//        }
//        dump($times);
//        echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
//        dd(1);
////

        foreach ($chunks as $chunk) {
            echo 'Время выполнения скрипта: ';
            $promise = Promise\settle($chunk);
            $results = $promise->wait();
            foreach ($results as $key => $r) {
                if ($r['state'] != 'rejected') {
                    $success[$key] = $r;
                }
            }
        }
        foreach ($results as $key => $r) {
            if ($r['state'] != 'rejected') {
                $success[] = $r;
            }
        }
        echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
        dump($success);
        dd($results);
    } catch (\GuzzleHttp\Exception\ConnectException $e) {
        dd(1);
    }


    $PARSER = new ParserService('<div><input type="hidden" autocomplete="off" name="listing_data_essential[331]" id="listing_data_essential[331]" value="{&quot;groupindex&quot;:&quot;331&quot;,&quot;carcode&quot;:&quot;1436031&quot;,&quot;parttype&quot;:&quot;2136&quot;,&quot;partkey&quot;:&quot;120543&quot;,&quot;opts&quot;:{&quot;0-0-0-1&quot;:{&quot;warehouse&quot;:&quot;92501&quot;,&quot;whpartnum&quot;:&quot;FEL 35063&quot;,&quot;optionlist&quot;:&quot;0&quot;,&quot;paramcode&quot;:&quot;0&quot;,&quot;notekey&quot;:&quot;0&quot;,&quot;multiple&quot;:&quot;1&quot;}}}"></div>>');
    $input_partKey = $PARSER->dom->find('input[name^=listing_data_essential]')[0];
    dd(json_decode(html_entity_decode($input_partKey->getAttribute('value')))->partkey);
    $rez = json_decode($input_partKey->getAttribute('value'))['partkey'];
    dd($rez);

    function convert1($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    dump(convert1(memory_get_usage(true)));
    $ProxyService = new ProxyService();

//    $proxies = \App\Models\Proxy::get()->pluck('proxy')->toArray();
////    $proxies =$ProxyService->fetchProxy();
//    $requests = $ProxyService->createAsyncRequestsArr($proxies);
//    $result = $ProxyService->checkProxyList($requests);
    $rez = $ProxyService->fetchAndSaveProxies();
    dd($rez);
    dump(convert1(memory_get_usage(true)));
    dd($rez);
    dd($result);
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
    $start = microtime(true);
//    $detailService = new \App\Services\DetailService();
//    $detailService->fetchDetailsInfo();
//    echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
//    dd(1);

    $httpClient = new \GuzzleHttp\Client();
    $response = $httpClient->get('https://www.rockauto.com/en/catalog/acura,2020,ilx,2.4l+l4,3445092,accessories,trailer+connector,2628', ['timeout' => 15,]
    );
    $html = (string)$response->getBody();
    dd($html);
    $request = new Request('GET', 'https://www.russianfood.com/');

    $client = new \GuzzleHttp\Client();
//    try {
//       throw new GrabberException('adas');
//    } catch (GrabberException $e) {
//        dd($e->getMessage());
//    }
//
//    dd((string)$response->getBody());
    $promises = [];
    for ($i = 1; $i <= 3; $i++) {
//        $httpClient = new \GuzzleHttp\Client();
//    $response = $httpClient->get('https://www.russianfood.com/');
        $url = 'https://povar.ru/';
        if ($i == 2) {
            $url = 'https://www.rockauto.com/';
        }
        $name = 'test' . $i;
        $promises[$name] = $client->getAsync($url, ['timeout' => 2, 'proxy' => '135.125.113.41:3128']);
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
