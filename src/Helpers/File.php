<?php

namespace Plank\Mediable\Helpers;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

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

    /**
     * Returns the extension based on the mime type.
     *
     * If the mime type is unknown, returns null.
     *
     * @param  string $mimeType
     * @return string|null The guessed extension or null if it cannot be guessed
     *
     * @see Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser
     */
    public static function guessExtension($mimeType)
    {
        $guesser = ExtensionGuesser::getInstance();

        return $guesser->guess($mimeType);
    }
}
