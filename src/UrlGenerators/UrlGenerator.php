<?php

namespace Frasmage\Mediable\UrlGenerators;

use Frasmage\Mediable\Media;

interface UrlGenerator{
    public function setMedia(Media $media);

    public function isPubliclyAccessible();

    public function getUrl();

    public function getDriver();
}
