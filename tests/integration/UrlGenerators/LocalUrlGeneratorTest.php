<?php

use Plank\Mediable\Exceptions\MediaUrlException;
use Plank\Mediable\UrlGenerators\LocalUrlGenerator;
use Plank\Mediable\Media;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Routing\UrlGenerator as Url;

class LocalUrlGeneratorTest extends TestCase
{
    public function test_it_generates_absolute_path()
    {
        $generator = $this->setupGenerator();
        $this->assertEquals(public_path('uploads/foo/bar.jpg'), $generator->getAbsolutePath());
    }

    public function test_it_generates_url()
    {
        $generator = $this->setupGenerator();
        $this->assertEquals('http://localhost/uploads/foo/bar.jpg', $generator->getUrl());
    }

    public function test_it_generates_custom_url()
    {
        $this->app['config']->set('filesystems.disks.uploads.url', 'http://example.com');
        $generator = $this->setupGenerator();
        $this->assertEquals('http://example.com/foo/bar.jpg', $generator->getUrl());
    }

    public function test_it_generates_prefixed_url()
    {
        $this->app['config']->set('filesystems.disks.public_storage.url', null);
        $this->app['config']->set('filesystems.disks.public_storage.prefix', 'uploads');
        $generator = $this->setupGenerator('public_storage');
        $this->assertEquals('http://localhost/uploads/foo/bar.jpg', $generator->getUrl());
    }

    public function test_it_generates_prefixed_custom_url()
    {
        $this->app['config']->set('filesystems.disks.public_storage.prefix', 'uploads');
        $generator = $this->setupGenerator('public_storage');
        $this->assertEquals('http://localhost/storage/uploads/foo/bar.jpg', $generator->getUrl());
    }

    public function test_it_throws_exception_for_non_public_disk()
    {
        $generator = $this->setupGenerator('tmp');
        $this->expectException(MediaUrlException::class);
        $generator->getPublicPath();
    }

    public function test_it_accepts_public_visibility()
    {
        $generator = $this->setupGenerator('public_storage');
        $this->assertEquals('http://localhost/storage/foo/bar.jpg', $generator->getUrl());
    }

    protected function setupGenerator($disk = 'uploads')
    {
        $media = factory(Media::class)->make([
            'disk' => $disk,
            'directory' => 'foo',
            'filename' => 'bar',
            'extension' => 'jpg'
        ]);
        $this->useFilesystem($disk);
        $this->seedFileForMedia($media);
        $generator = new LocalUrlGenerator(config(), url());
        $generator->setMedia($media);
        return $generator;
    }
}
