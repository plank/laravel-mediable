<?php

namespace Plank\Mediable\Tests\Mocks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Mediable\Mediable;
use Plank\Mediable\MediableInterface;

class SampleMediableSoftDelete extends Model implements MediableInterface
{
    use Mediable;
    use SoftDeletes;

    protected $table = 'sample_mediables';
    public $rehydrates_media = true;
}
