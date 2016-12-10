<?php

use Plank\Mediable\Exceptions\MediaUrlException;
use Plank\Mediable\UrlGenerators\S3UrlGenerator;
use Plank\Mediable\Media;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Routing\UrlGenerator as Url;

class S3UrlGeneratorTest extends TestCase
{
    public function setUp()
    {
        parent::setup();
        if (!$this->s3ConfigLoaded()) {
            $this->markTestSkipped('S3 Credentials not available.');
        }
    }

    public function test_it_generates_absolute_path()
    {
        $generator = $this->setupGenerator();
        $this->assertEquals('https://s3.amazonaws.com/' . env('S3_BUCKET') . '/foo/bar.jpg', $generator->getAbsolutePath());
    }

    public function test_it_generates_url()
    {
        $generator = $this->setupGenerator();
        $this->assertEquals('https://s3.amazonaws.com/' . env('S3_BUCKET') . '/foo/bar.jpg', $generator->getUrl());
    }

    public function test_it_throws_exception_if_not_available()
    {
        config()->set('filesystems.disks.s3.visibility', 'private');
        $generator = $this->setupGenerator();
        $this->expectException(MediaUrlException::class);
        $generator->getUrl();
    }

    protected function setupGenerator()
    {
        $media = factory(Media::class)->make([
            'disk' => 's3',
            'directory' => 'foo',
            'filename' => 'bar',
            'extension' => 'jpg'
        ]);
        $this->useFilesystem('s3');
        $generator = new S3UrlGenerator(config(), app(Illuminate\Filesystem\FilesystemManager::class));
        $generator->setMedia($media);
        return $generator;
    }
}
