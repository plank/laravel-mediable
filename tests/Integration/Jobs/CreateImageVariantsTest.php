<?php

namespace Plank\Mediable\Tests\Integration\Jobs;

use Plank\Mediable\ImageManipulation;
use Plank\Mediable\ImageManipulator;
use Plank\Mediable\Jobs\CreateImageVariants;
use Plank\Mediable\Tests\TestCase;

class CreateImageVariantsTest extends TestCase
{
    public function test_it_will_trigger_image_manipulation()
    {
        $model = $this->makeMedia(['aggregate_type' => 'image']);
        $variant1 = 'foo';
        $variant2 = 'bar';

        $manipulator = $this->createMock(ImageManipulator::class);
        $manipulator->expects($this->once())
            ->method('validateMedia')
            ->with($model);
        $manipulator->expects($this->exactly(2))
            ->method('getVariantDefinition')
            ->withConsecutive([$variant1], [$variant2])
            ->willReturn($this->createMock(ImageManipulation::class));
        $manipulator->expects($this->exactly(2))
            ->method('createImageVariant')
            ->withConsecutive([$model, $variant1, false], [$model, $variant2, false]);
        app()->instance(ImageManipulator::class, $manipulator);

        $job = new CreateImageVariants($model, [$variant1, $variant2]);
        $job->handle();
    }

    public function test_it_will_trigger_image_manipulation_recreate()
    {
        $model = $this->makeMedia(['aggregate_type' => 'image']);
        $variant1 = 'foo';
        $variant2 = 'bar';

        $manipulator = $this->createMock(ImageManipulator::class);
        $manipulator->expects($this->once())
            ->method('validateMedia')
            ->with($model);
        $manipulator->expects($this->exactly(2))
            ->method('getVariantDefinition')
            ->withConsecutive([$variant1], [$variant2])
            ->willReturn($this->createMock(ImageManipulation::class));
        $manipulator->expects($this->exactly(2))
            ->method('createImageVariant')
            ->withConsecutive([$model, $variant1, true], [$model, $variant2, true]);
        app()->instance(ImageManipulator::class, $manipulator);

        $job = new CreateImageVariants($model, [$variant1, $variant2], true);
        $job->handle();
    }
}
