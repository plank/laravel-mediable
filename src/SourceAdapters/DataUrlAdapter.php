<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;

/**
 * Adapts a string containing an RFC2397 Data URL.
 */
class DataUrlAdapter extends StreamAdapter
{
    public function __construct(string $source)
    {
        $source = preg_replace('/^data:\/?\/?/', 'data://', $source, -1, $count);
        if ($count === 0) {
            throw new \InvalidArgumentException('Invalid Data URL');
        }
        parent::__construct(Utils::streamFor(Utils::tryFopen($source, 'rb')));
    }
}
