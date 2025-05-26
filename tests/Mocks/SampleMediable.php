<?php

namespace Plank\Mediable\Tests\Mocks;

use Illuminate\Database\Eloquent\Model;
use Plank\Mediable\MediableInterface;
use Plank\Mediable\Mediable;

/**
 * @method static self first()
 */
class SampleMediable extends Model implements MediableInterface
{
    use Mediable;

    public bool $rehydrates_media = true;
}
