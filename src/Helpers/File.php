<?php

namespace Plank\Mediable\Helpers;

class File
{
    public static function cleanDirname($path)
    {
        $dirname = pathinfo($path, PATHINFO_DIRNAME);
        if($dirname == '.'){
            return '';
        }
        return trim($dirname, '/');
    }

    public static function readableSize($bytes, $precision = 1)
    {
        static $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        if ($bytes === 0) {
            return '0 '.$units[0];
        }
        $exponent = floor(log($bytes, 1024));
        $value = $bytes / pow(1024, $exponent);
        return round($value, $precision) . ' ' . $units[$exponent];
    }
}
