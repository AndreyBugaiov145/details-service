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
            $jnsData[] = ['jsn' => $jsn, 'title' => $a->text];
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
            $short_description_a_elems = $short_description_div->firstChild()->find('a');
            $short_description = '';
            foreach ($short_description_a_elems as $a) {
                $short_description .= $a->text;
            }
            $price = (float)substr($tbody->find('span.listing-price')[0]->firstChild()->text, 1);

            $detailsData[] = compact('title', 'short_description', 's_number', 'price');
        }

        return $detailsData;
    }


    public function isFinalCategory()
    {
        $divs = $this->dom->find('div.listing-container-border');

        return count($divs) > 0;
    }


}
