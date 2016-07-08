<?php

use Frasmage\Mediable\Exceptions\MediaUrlException;
use Frasmage\Mediable\UrlGenerators\LocalUrlGenerator;
use Frasmage\Mediable\Media;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Routing\UrlGenerator as Url;

class LocalUrlGeneratorTest extends TestCase
{
    public function test_it_generates_absolute_path(){
        $generator = $this->setupGenerator();
        $this->assertEquals(public_path('uploads/foo/bar.jpg'), $generator->getAbsolutePath());
    }

    public function test_it_generates_url(){
        $generator = $this->setupGenerator();
        $this->assertEquals('http://localhost/uploads/foo/bar.jpg', $generator->getUrl());
    }

    public function test_it_throws_exception_for_non_public_disk(){
        $generator = $this->setupGenerator('tmp');
        $this->expectException(MediaUrlException::class);
        $generator->getPublicPath();
    }

    protected function setupGenerator($disk = 'uploads'){
        $media = factory(Media::class)->make([
            'disk' => $disk,
            'directory' => 'foo',
            'filename' => 'bar',
            'extension' => 'jpg'
        ]);
        $this->seedFileForMedia($media);
        $generator = new LocalUrlGenerator(config(), url());
        $generator->setMedia($media);
        return $generator;
    }

}
