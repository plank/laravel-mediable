<?php

namespace Plank\Mediable\Jobs;

use Carbon\Traits\Serialization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Plank\Mediable\Exceptions\ImageManipulationException;
use Plank\Mediable\ImageManipulator;
use Plank\Mediable\Media;

class CreateImageVariants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, Serialization;

    /**
     * @var string[]
     */
    private $variantNames;
    /**
     * @var Media
     */
    private $model;

    /**
     * CreateImageVariants constructor.
     * @param Media $model
     * @param string|string[] $variantNames
     * @throws ImageManipulationException
     */
    public function __construct(Media $model, $variantNames)
    {
        $variantNames = (array) $variantNames;
        $this->validate($model, $variantNames);

        $this->variantNames = $variantNames;
        $this->model = $model;
    }

    public function handle()
    {
        foreach ($this->getVariantNames() as $variantName) {
            $this->getImageManipulator()->createImageVariant(
                $this->getModel(),
                $variantName
            );
        }
    }

    /**
     * @return string[]
     */
    public function getVariantNames(): array
    {
        return $this->variantNames;
    }

    /**
     * @return Media
     */
    public function getModel(): Media
    {
        return $this->model;
    }

    /**
     * @param Media $model
     * @param array $variantNames
     * @throws ImageManipulationException
     */
    private function validate(Media $media, array $variantNames): void
    {
        $imageManipulator = $this->getImageManipulator();
        $imageManipulator->validateMedia($media);
        foreach ($variantNames as $variantName) {
            $imageManipulator->getVariantDefinition($variantName);
        }
    }

    private function getImageManipulator(): ImageManipulator
    {
        return app(ImageManipulator::class);
    }
}
