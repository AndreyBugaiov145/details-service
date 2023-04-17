<?php

namespace App\Services;

use App\Utils\MemoryUtils;
use GuzzleHttp\Promise;
use Log;

class ProxyService
{
    protected $opt1 = [
        "timeout" => 10000,
        "protocol" => "all",
        "country" => "all",
        "ssl" => "all",
        "anonymity" => "all"
    ];

    protected $connect_timeout = 20;
    protected $timeout = 12;

    protected $url = 'https://www.rockauto.com/catalog/catalogapi.php';

    public function __construct()
    {
        $this->httpClient = $this->createHttpClient();
    }

    protected function createHttpClient()
    {
        return new \GuzzleHttp\Client([
            'headers' => [
                'Connection' => 'close',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
                'Origin' => 'https://www.rockauto.com',
            ],
            'verify' => false,
            'Connection' => 'close',
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,
        ]);
    }

    protected function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = $this->createHttpClient();
        }

        return $this->httpClient;
    }

    public function fetchAndSaveProxies()
    {
        $proxies = $this->fetchProxy();
        foreach ($proxies as &$proxy) {
            $proxy = trim($proxy);
        }

        $result = $this->checkProxyList($proxies);
        $this->saveProxies($result['success']);

        return $result['success'];
    }

    protected function fetchProxy()
    {
        try {
            $endpoint = new ProxyScrape($this->opt1);
            $proxies1 = $endpoint->get() ?: [];
        } catch (\Exception $e) {
            Log::debug('ProxyScrape ERROR');
            sleep(60*5);
            $proxies1 = [];
        }

        try {
            $proxyOrg = new ProxyOrg() ;
            $proxies2 = $proxyOrg->getProxies();
        } catch (\Exception $e) {
            $proxies2 = [];
        }


        MemoryUtils::monitoringMemory();
        unset($endpoint);
        unset($proxyOrg);
        gc_collect_cycles();

        return array_merge($proxies1, $proxies2);
    }

    protected function createAsyncRequestsArr($proxies)
    {
        $promises = [];

        foreach ($proxies as $proxy) {
            if ($proxy == '') {
                continue;
            }
            $promises[$proxy] = $this->getHttpClient()->postAsync(
                $this->url,
                [
                    'timeout' => $this->timeout,
                    'connect_timeout' => $this->connect_timeout,
                    'proxy' => $proxy,
                    'form_params' => [
                        'func' => 'getbuyersguide',
                        'scbeenloaded' => true,
                        'api_json_request' => 1,
                    ],
                    'headers' => [

                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
                    ],
                    'allow_redirects' =>false
                ]);
        }

        return $promises;
    }

    protected function checkProxyList(array $pr, $chunkCount = 1200)
    {
        $success = [];
        $failed = [];
        $chunks = array_chunk($pr, $chunkCount, true);
        try {
            foreach ($chunks as $i => $chunk) {
                $requests = $this->createAsyncRequestsArr($chunk);
                $promise = Promise\settle($requests);
                $results = $promise->wait();

                foreach ($results as $key => $r) {
                    if ($r['state'] != 'rejected') {
                        $success[] = $key;
                    } else {
                        $failed[] = $key;
                    }
                }
                unset($results);
                unset($promise);
                unset($requests);
                unset($requests);
                unset($this->httpClient);
                $this->httpClient = false;
                gc_collect_cycles();
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            \Log::critical($e->getMessage(), $e->getRequest());
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
//        $requests = $this->createAsyncRequestsArr($proxies);
        return $this->checkProxyList($proxies, 2000);
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
        Log::info('getProxies');
//        $proxiesArr1 = $this->getWorkingProxyAndUpdateFailedFromDB();
        $proxiesArr2 = $this->fetchAndSaveProxies();

        return array_unique(array_merge($proxiesArr2, []));
    }


}
