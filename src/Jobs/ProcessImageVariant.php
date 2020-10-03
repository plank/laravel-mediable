<?php

namespace Plank\Mediable\Jobs;

use Carbon\Traits\Serialization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Plank\Mediable\ImageManipulator;
use Plank\Mediable\Media;

class ProcessImageVariant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, Serialization;

    /**
     * @var string
     */
    private $variantName;
    /**
     * @var Media
     */
    private $model;

    public function __construct(string $variantName, Media $model)
    {
        $this->variantName = $variantName;
        $this->model = $model;
    }

    public function handle()
    {
        app(ImageManipulator::class)->createVariant(
            $this->getVariantName(),
            $this->getModel()
        );
    }

    /**
     * @return string
     */
    public function getVariantName(): string
    {
        return $this->variantName;
    }

    /**
     * @return Media
     */
    public function getModel(): Media
    {
        return $this->model;
    }
}
