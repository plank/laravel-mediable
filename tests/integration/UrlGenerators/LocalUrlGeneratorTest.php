<?php

use Illuminate\Filesystem\FilesystemManager;
use Plank\Mediable\Media;
use Plank\Mediable\UrlGenerators\LocalUrlGenerator;

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

    public function test_it_attempts_to_generate_url_for_non_public_disk()
    {
        $generator = $this->setupGenerator('tmp');
        $this->assertEquals('/storage/foo/bar.jpg', $generator->getUrl());
    }

    public function test_it_accepts_public_visibility()
    {
        $generator = $this->setupGenerator('public_storage');
        $this->assertEquals('http://localhost/storage/foo/bar.jpg', $generator->getUrl());
    }

    protected function setupGenerator($disk = 'uploads')
    {
        /** @var Media $media */
        $media = factory(Media::class)->make([
            'disk' => $disk,
            'directory' => 'foo',
            'filename' => 'bar',
            'extension' => 'jpg'
        ]);
        $this->useFilesystem($disk);
        $this->seedFileForMedia($media);
        $generator = new LocalUrlGenerator(config(), app(FilesystemManager::class));
        $generator->setMedia($media);
        return $generator;
    }
}
