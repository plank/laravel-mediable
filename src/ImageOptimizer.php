<?php

namespace Plank\Mediable;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Spatie\ImageOptimizer\OptimizerChain;

class ImageOptimizer
{
    public function optimizeImage(StreamInterface $imageStream, OptimizerChain $optimizerChain): StreamInterface
    {
        // CLI optimizers require the file to be on disk so write it to /tmp
        $tmpPath = $this->getTmpFile();
        $tmpStream = Utils::streamFor(Utils::tryFopen($tmpPath, 'wb'));
        Utils::copyToStream($imageStream, $tmpStream);
        $optimizerChain->optimize($tmpPath);

        // open a separate stream to detect the changes made by the optimizers
        return Utils::streamFor(Utils::tryFopen($tmpPath, 'rb'));
    }

    private function getTmpFile(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'mediable-');
        if ($tmpFile === false) {
            throw new \RuntimeException(
                'Could not create temporary file. The system temp directory may not be writable.'
            );
        }

        return $tmpFile;
    }
}
