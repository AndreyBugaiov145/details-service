<?php

namespace App\Services;

use DiDom\Document;

class ProxyOrg
{
    protected $url = 'https://www.us-proxy.org/';

    function __construct()
    {
        $this->httpClient = new \GuzzleHttp\Client([
            'headers' => [
                'Connection' => 'close',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
            ],
            'timeout' => 10,
            'connect_timeout' => 10,
            'Connection' => 'close',
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,
        ]);
    }

    public function getProxies()
    {
        \Log::info('getProxies ORG');
        $response = $this->httpClient->get($this->url);
    \Log::info('getProxies ORG $html');
        $html = (string)$response->getBody();
        \Log::info('getProxies ORG Document');

        $dom = new Document($html);
        \Log::info('getProxies ORG Document find');

        $textarea = $dom->find('textarea');
        $matches = [];
        \Log::info('getProxies ORG preg_match_all');

        preg_match_all('~([0-9]*\.[0-9]*\.[0-9]*\.[0-9]*:[0-9]*)~', $textarea[0]->text(), $matches);

        \Log::info('getProxies ORG $matches',$matches[0]);

        return count($matches) && isset($matches[0]) ? $matches[0] : [];
    }
}
