<?php

namespace App\Utils;

class ArrayUtils
{
    public static function getFirstItem(array $array)
    {
        $item = reset($array);

        return $item ?: [];
    }

}
