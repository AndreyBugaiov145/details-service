<?php

namespace App\Services;

use Arr;
use GuzzleHttp\Promise;

class FetchingService
{
    public $httpClient;
    protected $fetchUrl = 'https://www.rockauto.com/catalog/catalogapi.php';
    protected $mainPageUrl = 'https://www.rockauto.com/';
    protected $timeout = 30;
    protected $connect_timeout = 20;
    protected $proxies = [];
    protected $proxyService;
    protected $chunkCount = 500;

    public function __construct()
    {
        $this->httpClient = new \GuzzleHttp\Client([
            'headers' => ['Connection' => 'close'],
            'Connection' => 'close',
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,
        ]);
        $this->proxyService = new ProxyService();
        $this->proxies = $this->proxyService->getProxies();
    }

    protected function getProxies(int $count = 15)
    {
        $count = $count / 10 > 20 ? $count / 10 : 20;
        if (count($this->proxies) < $count) {
            $this->proxies = $this->proxyService->getProxies();
            \Log::info('need count' . $count . '.Get new proxies' . count($this->proxies));
        }

        return $this->proxies;
    }

    protected function deleteProxy($proxy)
    {
        $this->proxies = array_filter($this->proxies, function ($item) use ($proxy) {
            return $item != $proxy;
        });
    }

    protected function getNavNodeFetchFormData($jsn)
    {
        return [
            'payload' => json_encode([
                'jsn' => $jsn
            ]),
            'func' => 'navnode_fetch',
            'api_json_request' => 1,
        ];
    }

    protected function getHeaders()
    {
        return ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36'];
    }

    protected function getBuyersGuideFetchFormData($partkey)
    {
        return [
            'payload' => json_encode([
                'partData' => [
                    'listing_data_essential' => [
                        'partkey' => $partkey
                    ]
                ]
            ]),
            'func' => 'getbuyersguide',
            'scbeenloaded' => true,
            'api_json_request' => 1,
        ];
    }

    public function getMainPage(): string
    {
        $proxy = Arr::random($this->getProxies());
        try {
            $response = $this->httpClient->get($this->mainPageUrl, [
                'headers' => $this->getHeaders(),
                'timeout' => $this->timeout,
                'connect_timeout' => $this->connect_timeout,
                'proxy' => $proxy,
            ]);
        } catch (\Exception $e) {
            $this->proxyService->incrementFailedProxy([$proxy]);
            $this->deleteProxy($proxy);
            return false;
        }

        return (string)$response->getBody();
    }

    protected function getAsyncRequestChildCategory(array $jsn, string $proxy)
    {
        return $this->httpClient->postAsync($this->fetchUrl, [
            'form_params' => $this->getNavNodeFetchFormData($jsn),
            'headers' => $this->getHeaders(),
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connect_timeout,
            'proxy' => $proxy,
        ]);
    }

    public function getAsyncChildCategories(array $data)
    {
        $failedProxy = [];

        $chunks = array_chunk($data, $this->chunkCount);
        $result = [];
        foreach ($chunks as $i => $chunk) {
            $promises = [];
            $proxies = array_values($this->getProxies(count($chunk)));
            do {
                $proxies = array_merge($proxies, $proxies);
            } while (count($proxies) / count($chunk) < 1);

            $j = 0;
            foreach ($chunk as $item) {
//                $proxy = Arr::random($this->getProxies(count($chunk)));
                $uid = isset($item['uid']) ? $item['uid'] : $item['title'];
                $key = $uid . '|' . $proxies[$j];
                $promises[$key] = $this->getAsyncRequestChildCategory($item['jsn'], $proxies[$j]);
                $j++;
            }
            $responses = Promise\settle($promises)->wait();

            foreach ($responses as $key => $responseArr) {
                try {
                    [$uid, $proxy] = explode('|', $key);
                } catch (\Exception $e) {

                }
                $result[$uid] = $responseArr;
                if ($responseArr['state'] === 'rejected') {
                    $failedProxy[] = $proxy;
                    $this->deleteProxy($proxy);
                }
            }
            $this->proxyService->incrementFailedProxy($failedProxy);
            \Log::info('Request sending progress ' . 100 * ($j + 1) / count($chunks));
        }

        return $result;
    }

    protected function getAsyncRequestDetailBuyersGuide($partkey, string $proxy)
    {
        return $this->httpClient->postAsync($this->fetchUrl, [
            'form_params' => $this->getBuyersGuideFetchFormData($partkey),
            'headers' => $this->getHeaders(),
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connect_timeout,
            'proxy' => $proxy,
        ]);
    }

    public function getAsyncDetailsBuyersGuid(array $data)
    {
        $failedProxy = [];

        $chunks = array_chunk($data, $this->chunkCount);
        $result = [];

        foreach ($chunks as $i => $chunk) {
            $promises = [];
            $proxies = array_values($this->getProxies(count($chunk)));
            do {
                $proxies = array_merge($proxies, $proxies);
            } while (count($proxies) / count($chunk) < 1);

            $j = 0;
            foreach ($chunk as $item) {
//                $proxy = Arr::random($this->getProxies(count($chunk)));
                $uid = $item['partkey'];
                $key = $uid . '|' . $proxies[$j];
                $promises[$key] = $this->getAsyncRequestDetailBuyersGuide($item['partkey'], $proxies[$j]);
                $j++;
            }

            $responses = Promise\settle($promises)->wait();
            foreach ($responses as $key => $responseArr) {
                try {
                    [$uid, $proxy] = explode('|', $key);
                } catch (\Exception $e) {

                }
                $result[$uid] = $responseArr;
                if ($responseArr['state'] === 'rejected') {
                    $failedProxy[] = $proxy;
                    $this->deleteProxy($proxy);
                }
            }
            $this->proxyService->incrementFailedProxy($failedProxy);
            \Log::info('Request sending progress ' . 100 * ($i + 1) / count($chunks));
        }

        return $result;
    }


}
