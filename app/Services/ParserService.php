<?php

namespace App\Services;

use PHPHtmlParser\Dom;

class ParserService
{
    public function __construct($html)
    {
        $this->dom = new Dom;
        $this->dom->loadStr($html);
    }

    public function getAllChildCategoriesWithJns(): array
    {
        $divs = $this->dom->find('div.ranavnode');

        $jnsData = [];
        foreach ($divs as $div) {
            $jsn = (array)json_decode(html_entity_decode($div->firstChild()->getAttribute('value')));
            $a = $div->find('a.navlabellink')[0];
            $jsn['href'] = 'https://www.rockauto.com' . $a->getAttribute('href');
            $jnsData[] = [
                'jsn' => $jsn,
                'title' => $a->text
            ];
        }

        return $jnsData;
    }

    public function getDetails(): array
    {
        $tbodies = $this->dom->find('tbody');
        $tbodiesFiltered = [];
        $detailsData = [];

        foreach ($tbodies as $tbody) {
            if ($tbody->getAttribute('id')) {
                $tbodiesFiltered[] = $tbody;
            }
        }

        foreach ($tbodiesFiltered as $tbody) {
            $title1 = $tbody->find('span.listing-final-manufacturer')[0]->text;
            $title2_elem = $tbody->find('.span-link-underline-remover')[0];
            $title2 = !is_null($title2_elem) ? $title2_elem->text : '';
            $title = $title1 . ' ' . $title2;
            $s_number = $tbody->find('span.listing-final-partnumber')[0]->text;
            $short_description_div = $tbody->find('div.listing-text-row')[0];
            $short_description_a_elems = $short_description_div->firstChild()->find('span');
            $short_description = '';
            foreach ($short_description_a_elems as $span) {
                $short_description .= $span->text;
            }

            $result = [];
            preg_match("~[^\d]*(\d*\.?\d*)[^\d]*~", $tbody->find('span.listing-price')[0]->firstChild()->text, $result);
            if (isset($result[1])) {
                $price = $result[1] ?: 0.0;
            } else {
                $price = 0.0;
            }

            $input_partKey = $tbody->find('input[name^=listing_data_essential]')[0];
            $partkey = null;
            if ($input_partKey) {
                try {
                    $partkey = optional(json_decode(html_entity_decode($input_partKey->getAttribute('value'))))->partkey;
                } catch (\Exception $e) {

                }
            }
            $detailsData[] = compact('title', 'short_description', 's_number', 'price', 'partkey');
        }

        return $detailsData;
    }


    public function isFinalCategory()
    {
        $divs = $this->dom->find('div.listing-container-border');

        return count($divs) > 0;
    }

    public function getAnalogyDetails()
    {
        $data = [];
        $trs = $this->dom->find('div.buyersguide-nested table tr');
        foreach ($trs as $tr) {
            $tds = $tr->find('td');
            if (count($tds) > 2) {
                $data[] = [
                    'brand' => optional($tds[0])->text,
                    'model' => optional($tds[1])->text,
                    'years' => optional($tds[2])->text,
                ];
            } else {
                $data[] = [
                    'brand' => optional($tds[0])->text,
                    'years' => optional($tds[1])->text,
                    'model' => null,
                ];
            }
        }
        return $data;
    }
}
