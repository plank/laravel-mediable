<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;

/**
 * Raw content Adapter.
 *
 * Adapts a string representing raw contents.
 */
class DataUrlAdapter extends StreamAdapter
{
    /**
     * Constructor.
     * @param string $source
     */
    public function __construct(string $source)
    {
        $source = preg_replace('/^data:\/?\/?/', 'data://', $source);
        parent::__construct(Utils::streamFor(Utils::tryFopen($source, 'rb')));
    }
}
