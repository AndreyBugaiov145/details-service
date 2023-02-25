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

    protected $url = 'https://www.rockauto.com/';

    public function __construct()
    {
        $this->httpClient = new \GuzzleHttp\Client();
    }

    public function fetchAndSaveProxies()
    {
        $proxies = $this->fetchProxy();
        $requests = $this->createAsyncRequestsArr($proxies);
        $result = $this->checkProxyList($requests);
        $this->saveProxies($result);
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
                    'timeout' => 40,
                    'connect_timeout' => 20,
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
                        }
                    }
                } catch (\GuzzleHttp\Exception\ConnectException $e) {
                    \Log::critical($e->getMessage(), $e->getRequest());
                }
            }
        }
        return array_unique($success);
    }

    protected function saveProxies(array $proxies)
    {
        $data = [];
        foreach ($proxies as $proxy) {
            $data[] = ['proxy' => $proxy];
        }
        \App\Models\Proxy::upsert($data, ['proxy'], ['fail_count' => 0]);

        dd($proxies);
    }
}
