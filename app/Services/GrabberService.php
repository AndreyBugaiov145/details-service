<?php

namespace App\Services;

use GuzzleHttp\Promise;

class GrabberService
{
    public $httpClient;
    protected $fetchUrl = 'https://www.rockauto.com/catalog/catalogapi.php';
    protected $mainPageUrl = 'https://www.rockauto.com/';
    protected $timeOut = 15;

    public function __construct()
    {
        $this->httpClient = new \GuzzleHttp\Client();
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
            'api_json_request' => 1,
        ];
    }

    public function getMainPage(): string
    {
        $response = $this->httpClient->get($this->mainPageUrl,[
            'timeout' => $this->timeOut,
//            'proxy' => '68.183.103.250:3128'
        ]);

        return (string)$response->getBody();
    }

    public function getChildCategories(array $jsn): string
    {
        $response = $this->httpClient->post($this->mainPageUrl, [
            'form_params' => $this->getNavNodeFetchFormData($jsn),
            'timeout' => $this->timeOut,
//            'proxy' => '68.183.103.250:3128'
        ]);


        $data = json_decode((string)$response->getBody(), true);
        if (isset($data['html_fill_sections']) && is_array($data['html_fill_sections'])) {
            return reset($data['html_fill_sections']);
        }

        return '';
    }

    public function getAsyncChildCategory(array $jsn)
    {
        return $this->httpClient->postAsync($this->mainPageUrl, [
            'form_params' => $this->getNavNodeFetchFormData($jsn),
            'timeout' => $this->timeOut,
//            'proxy' => '68.183.103.250:3128'
        ]);
    }

    public function getAsyncChildCategories(array $data)
    {
        $promises = [];
        foreach ($data as $item){
            $promises[$item['title']] = $this->getAsyncChildCategory($item['jsn']);
        }

        return Promise\settle($promises)->wait();
    }

    public function getDetailBuyersGuide($href): string
    {
        $response = $this->httpClient->post($this->mainPageUrl, [
            'form_params' => $this->getBuyersGuideFetchFormData($href),
            'timeout' => $this->timeOut,
//            'proxy' => '68.183.103.250:3128'
        ]);

        $data = json_decode((string)$response->getBody(), true);
        if (isset($data['buyersguidepieces']) && isset($data['buyersguidepieces']['body'])) {
            return $data['buyersguidepieces']['body'];
        }

        return '';
    }

}
