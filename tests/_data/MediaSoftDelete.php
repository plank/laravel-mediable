<?php

use Plank\Mediable\Media;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaSoftDelete extends Media
{
    use SoftDeletes;

    protected $table = 'media';
}
