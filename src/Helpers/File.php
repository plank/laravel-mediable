<?php

namespace Plank\Mediable\Helpers;

/**
 * File Helpers.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class File
{
    /**
     * Get the directory name of path, trimming unecessary `.` and `/` characters.
     * @param  string $path
     * @return string
     */
    public static function cleanDirname($path)
    {
        $dirname = pathinfo($path, PATHINFO_DIRNAME);
        if ($dirname == '.') {
            return '';
        }

        return trim($dirname, '/');
    }

    /**
     * Generate a human readable bytecount string.
     * @param  int  $bytes
     * @param  int $precision
     * @return string
     */
    public static function readableSize($bytes, $precision = 1)
    {
        static $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        if ($bytes === 0) {
            return '0 '.$units[0];
        }
        $exponent = floor(log($bytes, 1024));
        $value = $bytes / pow(1024, $exponent);

        return round($value, $precision).' '.$units[$exponent];
    }
}
