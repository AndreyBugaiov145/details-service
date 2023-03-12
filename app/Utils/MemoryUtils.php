<?php

namespace App\Utils;

class MemoryUtils
{
    static public function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    static public function getUsedMemory()
    {
        return self::convert(memory_get_usage(true));
    }

    static public function loggingUsedMemory()
    {
        \Log::debug('Used memory = ' . self::getUsedMemory());
    }

}
