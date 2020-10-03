<?php

namespace Plank\Mediable\Tests\Integration\Jobs;

use Plank\Mediable\ImageManipulator;
use Plank\Mediable\Jobs\ProcessImageVariant;
use Plank\Mediable\Tests\TestCase;

class ProcessImageVariantTest extends TestCase
{
    public function test_it_will_trigger_image_manipulation()
    {
        $model = $this->makeMedia([]);
        $variant = 'foo';
        $job = new ProcessImageVariant($variant, $model);

        $manipulator = $this->createMock(ImageManipulator::class);
        $manipulator->expects($this->once())
            ->method('createVariant')
            ->with($variant, $model);
        app()->instance(ImageManipulator::class, $manipulator);

        $job->handle();
    }
}
