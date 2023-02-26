<?php

namespace App\Services;

use GuzzleHttp\Promise;

class ProxyService
{
    protected $opt1 = [
        "timeout" => 3500,
        "protocol" => "http",
        "country" => "all",
        "ssl" => "all",
        "anonymity" => "all"
    ];

    protected $opt2 = [
        "timeout" => 3500,
        "protocol" => "https",
        "country" => "all",
        "ssl" => "all",
        "anonymity" => "all"
    ];

    protected $connect_timeout = 40;
    protected $timeout = 20;

    protected $url = 'https://www.rockauto.com/';

    public function __construct()
    {
        $this->httpClient = new \GuzzleHttp\Client([
            'headers' => ['Connection' => 'close'],
            'Connection' => 'close',
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,
        ]);
    }

    public function fetchAndSaveProxies()
    {
        $proxies = $this->fetchProxy();
        $requests = $this->createAsyncRequestsArr($proxies);
        $result = $this->checkProxyList($requests);
        $this->saveProxies($result['success']);

        return $result['success'];
    }

    protected function fetchProxy()
    {
        $endpoint = new ProxyScrape($this->opt1);
        $list1 = $endpoint->get();

        $endpoint = new ProxyScrape($this->opt2);
        $list2 = $endpoint->get();

        return array_merge($list2, $list1) ?: [];
    }

    protected function createAsyncRequestsArr($proxies)
    {
        $promises = [];
        foreach ($proxies as $proxy) {
            if ($proxy == '') {
                continue;
            }
            $promises[$proxy] = $this->httpClient->getAsync(
                $this->url,
                [
                    'timeout' => $this->timeout,
                    'connect_timeout' => $this->connect_timeout,
                    'proxy' => $proxy,
                    'headers' => [

                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
                    ],
                    'allow_redirects' => [
                        'max' => 10,        // allow at most 10 redirects.
                        'strict' => true,      // use "strict" RFC compliant redirects.
                        'referer' => true,      // add a Referer header
                        'protocols' => ['https', 'http'], // only allow https URLs
                        'track_redirects' => true
                    ]
                ]);
        }

        return $promises;
    }

    protected function checkProxyList(array $requests)
    {
        $success = [];
        $failed = [];
        $chunks = array_chunk($requests, 100, true);
        foreach ($chunks as $chunk) {
            for ($i = 0; $i < 5; $i++) {
                try {
                    $promise = Promise\settle($chunk);
                    $results = $promise->wait();

                    foreach ($results as $key => $r) {
                        if ($r['state'] != 'rejected') {
                            unset($chunk[$key]);
                            $success[] = $key;
                        } else {
                            $failed[] = $key;
                        }
                    }
                } catch (\GuzzleHttp\Exception\ConnectException $e) {
                    \Log::critical($e->getMessage(), $e->getRequest());
                }
            }
        }
        return [
            'success' => array_unique($success),
            'failed' => array_unique($failed)
        ];
    }

    protected function saveProxies(array $proxies)
    {
        $data = [];
        foreach ($proxies as $proxy) {
            $data[] = ['proxy' => $proxy];
        }
        \App\Models\Proxy::upsert($data, ['proxy'], ['fail_count' => 0]);
    }

    protected function getCheckredProxyFromDB()
    {
        $proxies = \App\Models\Proxy::get()->pluck('proxy')->toArray();
        $requests = $this->createAsyncRequestsArr($proxies);
        return $this->checkProxyList($requests);
    }

    public function getWorkingProxyAndUpdateFailedFromDB()
    {
        $proxies = $this->getCheckredProxyFromDB();
        $this->incrementFailedProxy($proxies['failed']);

        return $proxies['success'];
    }

    public function incrementFailedProxy(array $proxies)
    {
        \App\Models\Proxy::whereIn('proxy', $proxies)->increment('fail_count');
    }

    public function getProxies()
    {
        $proxiesArr1 =  $this->getWorkingProxyAndUpdateFailedFromDB();
        $proxiesArr2 = $this->fetchAndSaveProxies();
dump('getProxies');
        return array_unique(array_merge($proxiesArr1, $proxiesArr2));
    }


}
