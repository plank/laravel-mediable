<?php

namespace Plank\Mediable\Tests\Mocks;

use Illuminate\Database\Eloquent\Model;
use Plank\Mediable\Mediable;
use Plank\Mediable\MediableInterface;

/**
 * @method static self first()
 */
class SampleMediable extends Model implements MediableInterface
{
    use Mediable;

    public bool $rehydrates_media = true;
}
