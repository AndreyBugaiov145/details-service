<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GrabberService
{
    public $httpClient;
    protected $fetchUrl = 'https://www.rockauto.com/catalog/catalogapi.php';
    protected $mainPageUrl = 'https://www.rockauto.com';

    public function __construct()
    {
        $this->httpClient = new \GuzzleHttp\Client();
    }

    protected function getNavNodeFetchFormData($jns)
    {
        return [
            'payload' => json_encode([
                'jsn' => $jns
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
        $response = $this->httpClient->get($this->mainPageUrl);

        return (string)$response->getBody();
    }

    public function getChildCategories($jns): string
    {
        $response = $this->httpClient->post($this->mainPageUrl, [
            'form_params' => $this->getNavNodeFetchFormData($jns)
        ]);


        $data = json_decode((string)$response->getBody(), true);
        if (isset($data['html_fill_sections']) && isset($data['html_fill_sections']['navchildren[]'])) {
            return $data['html_fill_sections']['navchildren[]'];
        }

        return '';
    }

    public function getDetailBuyersGuide($href): string
    {
        $response = $this->httpClient->post($this->mainPageUrl, [
            'form_params' => $this->getBuyersGuideFetchFormData($href)
        ]);

        $data = json_decode((string)$response->getBody(), true);
        if (isset($data['buyersguidepieces']) && isset($data['buyersguidepieces']['body'])) {
            return $data['buyersguidepieces']['body'];
        }

        return '';
    }

}
