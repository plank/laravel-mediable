<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;

/**
 * Adapts a string containing an RFC2397 Data URL.
 */
class DataUrlAdapter extends StreamAdapter
{
    public function __construct(string $source)
    {
        $source = preg_replace('/^data:\/?\/?/', 'data://', $source, -1, $count);
        if ($count === 0) {
            throw ConfigurationException::invalidSource('Invalid Data URL');
        }
        parent::__construct(Utils::streamFor(Utils::tryFopen($source, 'rb')));
    }
}
