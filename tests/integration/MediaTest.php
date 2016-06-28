<?php

use Frasmage\Mediable\Media;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MediaTest extends TestCase{
    use DatabaseMigrations;

    public function test_it_has_path_accessors(){
        $media = new Media;
        $media->disk = 'tmp';
        $media->directory = 'a/b/c';
        $media->filename = 'foo.bar';
        $media->extension = 'jpg';

        $this->assertEquals($this->tmpPath('a/b/c/foo.bar.jpg'), $media->absolutePath());
        $this->assertEquals($this->tmpPath('/a/b/c'), $media->dirname);
        $this->assertEquals('a/b/c/foo.bar.jpg', $media->diskPath());
        $this->assertEquals('a/b/c', $media->directory);
        $this->assertEquals('foo.bar.jpg', $media->basename);
        $this->assertEquals('foo.bar', $media->filename);
        $this->assertEquals('jpg', $media->extension);
    }
}
