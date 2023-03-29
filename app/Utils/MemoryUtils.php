<?php

namespace App\Utils;

class MemoryUtils
{
    protected static $memory = 0;

    static public function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    static public function getUsedMemory()
    {
        $memory = self::$memory > memory_get_usage(true) ? self::$memory : memory_get_usage(true);
        return self::convert($memory);
    }

    static public function loggingUsedMemory()
    {
        \Log::debug('Used memory = ' . self::getUsedMemory());
    }

    static public function monitoringMemory()
    {
        if (self::$memory < memory_get_usage(true)) {
            self::$memory = memory_get_usage(true);
        }
    }
}
