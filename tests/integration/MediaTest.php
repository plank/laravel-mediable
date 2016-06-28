<?php

use Frasmage\Mediable\Media;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MediaTest extends TestCase{
    use DatabaseMigrations;

    public function test_it_has_path_accessors(){
        $media = factory(Media::class)->make([
            'disk' => 'tmp',
            'directory' => 'a/b/c',
            'filename' => 'foo.bar',
            'extension' => 'jpg',
        ]);

        $this->assertEquals($this->tmpPath('a/b/c/foo.bar.jpg'), $media->absolutePath());
        $this->assertEquals($this->tmpPath('/a/b/c'), $media->dirname);
        $this->assertEquals('a/b/c/foo.bar.jpg', $media->diskPath());
        $this->assertEquals('a/b/c', $media->directory);
        $this->assertEquals('foo.bar.jpg', $media->basename);
        $this->assertEquals('foo.bar', $media->filename);
        $this->assertEquals('jpg', $media->extension);
    }

    public function test_it_can_be_queried_by_directory(){
        $media = factory(Media::class)->create(['directory' => 'foo']);
        $media = factory(Media::class)->create(['directory' => 'foo']);
        $media = factory(Media::class)->create(['directory' => 'bar']);
        $media = factory(Media::class)->create(['directory' => 'foo/baz']);

        $this->assertEquals(2, Media::inDirectory('tmp', 'foo')->count());
        $this->assertEquals(1, Media::inDirectory('tmp', 'foo/baz')->count());
        $this->assertEquals(3, Media::inDirectory('tmp', 'foo', true)->count());
    }

    protected function seedFileForMedia($media){
        app('filesystem')->disk($media->disk)->put($media->diskPath(), '');
    }
}
