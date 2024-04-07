<?php

namespace Plank\Mediable\Tests\Integration;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\ImageOptimizer;
use Plank\Mediable\Tests\TestCase;
use Spatie\ImageOptimizer\OptimizerChain;

class ImageOptimizerTest extends TestCase
{
    public function test_it_can_optimize_an_image(): void
    {
        $imageStream = Utils::streamFor(file_get_contents($this->sampleFilePath()));

        $imageOptimizer = new ImageOptimizer();
        $optimizerChain = $this->createMock(OptimizerChain::class);
        $optimizerChain->expects($this->once())
            ->method('optimize');

        $optimizedStream = $imageOptimizer->optimizeImage($imageStream, $optimizerChain);
        $imageStream->rewind();
        $optimizedStream->rewind();

        $this->assertNotSame($imageStream, $optimizedStream);
        $this->assertEquals($imageStream->getContents(), $optimizedStream->getContents());
    }
}
