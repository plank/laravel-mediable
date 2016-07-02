<?php

use Illuminate\Database\Eloquent\Model;
use Frasmage\Mediable\Mediable;

class SampleMediable extends Model
{
    use Mediable;

    public $rehydrates_media = true;
}
