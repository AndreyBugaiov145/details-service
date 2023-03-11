<?php

namespace App\Services;

use App\Models\Currency;
use PHPHtmlParser\Dom;

class CurrencyService
{
    public function __construct()
    {
        $this->httpClient = new \GuzzleHttp\Client([
            'headers' => ['Connection' => 'close'],
            'Connection' => 'close',
        ]);

    }

    public function updateUAHRate()
    {
        $html = $this->getCurrencyRatePage();
        $rate = $this->getCurrencyUAHRateInHTML($html);
        $rate = implode('.', explode(',', $rate));
        if ($rate) {
            Currency::where('code', Currency::UAH_CODE)->update(['rate' => $rate]);
        }
    }

    public function getCurrencyUAHRateInHTML($html)
    {
        $rate = null;
        $dom = new Dom;
        $dom->loadStr($html);

        $div = $dom->find('div.sale')[0];
        if ($div) {
            $span = $div->find('span.Typography ')[0];
            $rate = $span->text;
        }

        return $rate;
    }

    public function getCurrencyRatePage(): string
    {
        $response = $this->httpClient->get(
            'https://minfin.com.ua/currency/auction/usd/buy/all/',
            [
                'timeout' => 30,
                'connect_timeout' => 15,
            ]);

        return (string)$response->getBody();
    }

}
