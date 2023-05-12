<?php

namespace App\Services;

use DiDom\Document;

class ParserService
{
    public function __construct($html)
    {
        $this->dom = new Document($html);
    }

    public function getAllChildCategoriesWithJns(): array
    {
        $divs = $this->dom->find('div.ranavnode');

        $jnsData = [];
        foreach ($divs as $div) {
            $jsn = (array)json_decode(html_entity_decode($div->firstChild()->getAttribute('value')));
            $a = optional($div->find('a.navlabellink'))[0];
            if (is_null($a)) {
                continue;
            }
            if ($a) {
                $jsn['href'] = 'https://www.rockauto.com' . $a->getAttribute('href');
                $jnsData[] = [
                    'jsn' => $jsn,
                    'title' => $a->text()
                ];
            }
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
            $title1 = $tbody->find('span.listing-final-manufacturer')[0]->text();
            $title2_elem = optional($tbody->find('.span-link-underline-remover'))[0];
            $title2 = !is_null($title2_elem) ? $title2_elem->text() : '';
            $title = mb_substr($title1 . ' ' . $title2, 0, 250);
            $s_number = $tbody->find('span.listing-final-partnumber')[0]->text();
            $short_description_div = $tbody->find('div.listing-text-row')[0];
            $short_description_a_elems = $short_description_div->firstChild()->find('span');
            $short_description = '';
            $a_info_link = $tbody->find('a.ra-btn-moreinfo');
            $info_link = null;
            if ($a_info_link && $a_info_link[0]) {
                $info_link = $a_info_link[0]->getAttribute('href');
            }
            foreach ($short_description_a_elems as $span) {
                $short_description .= $span->text();
            }

            // Get EOM numbers
            $eom_numbers = $tbody->find('span[title^=Replaces these Alternate/ OE Part Numbers]');
            $is_fetched_i_n = false;
            $interchange_numbers = null;
            if ($eom_numbers && $eom_numbers[0]) {
                $is_fetched_i_n= true;
                $interchange_numbers= $eom_numbers[0]->text();
            }

            $result = [];
            preg_match("~[^\d]*(\d*\.?\d*)[^\d]*~", $tbody->find('span.listing-price')[0]->firstChild()->text(), $result);
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
            $detailsData[] = compact('title', 'short_description', 's_number', 'price', 'partkey', 'info_link','is_fetched_i_n' ,'interchange_numbers');
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
        foreach ($trs as $i => $tr) {
            $tds = $tr->find('td');
            if (count($tds) > 2) {
                $data[] = [
                    'id' => $i + 1,
                    'brand' => optional($tds[0])->text(),
                    'model' => optional($tds[1])->text(),
                    'years' => optional($tds[2])->text(),
                ];
            } else {
                $data[] = [
                    'id' => $i + 1,
                    'brand' => optional($tds[0])->text(),
                    'years' => optional($tds[1])->text(),
                    'model' => null,
                ];
            }
        }
        return $data;
    }

    public function getEOMNumbersDetails()
    {
        $numbers = '';
        $section = $this->dom->find('td section[aria-label^=Alternate/OEM Part Number(s)]');
        if ($section && $section[0]) {
            $numbers = trim(str_replace('OEM / Interchange Numbers:', '', $section[0]->text()));
        }

        return $numbers;
    }
}
