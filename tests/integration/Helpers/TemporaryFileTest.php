<?php

use Plank\Mediable\Helpers\TemporaryFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class TemporaryFileTest extends TestCase
{
    public function test_it_accepts_string_contents()
    {
        $path = realpath(__DIR__.'/../../_data/plank.png');
        $file = new TemporaryFile(file_get_contents($path), 'plank.png');

        $this->assertFileEquals($path, $file->getRealPath());
    }

    public function test_it_accepts_array_contents()
    {
        $file = new TemporaryFile(['foo', 'bar'], 'foobar');

        $this->assertStringEqualsFile($file->getRealPath(), 'foobar');
    }

    public function test_it_accepts_stream_contents()
    {
        $path = realpath(__DIR__.'/../../_data/plank.png');
        $file = new TemporaryFile(fopen($path, 'r'), 'plank.png');

        $this->assertFileEquals($path, $file->getRealPath());
    }

    public function test_it_prefixes_the_temporary_file_with_the_filename()
    {
        $path = realpath(__DIR__.'/../../_data/plank.png');
        $file = new TemporaryFile(file_get_contents($path), 'plank.png');

        $this->assertStringStartsWith('plank', $file->getFilename());
    }

    public function test_it_stores_the_file_in_the_temp_directory()
    {
        $path = realpath(__DIR__.'/../../_data/plank.png');
        $file = new TemporaryFile(file_get_contents($path), 'plank.png');

        $this->assertContains(sys_get_temp_dir(), $file->getPath());
    }

    public function test_it_throws_an_exception_if_there_are_no_contents()
    {
        $this->setExpectedException(FileNotFoundException::class);
        $file = new TemporaryFile('', 'empty.jpg');
    }

    public function test_it_is_invalid_if_there_are_no_contents()
    {
        $file = new TemporaryFile('', 'empty.jpg', false);

        $this->assertFalse($file->isFile());
    }

    public function test_it_returns_the_original_filename()
    {
        $path = realpath(__DIR__.'/../../_data/plank.png');
        $file = new TemporaryFile(file_get_contents($path), 'blank.png');

        $this->assertEquals('blank', $file->getOriginalName());
    }

    public function test_it_returns_the_original_extension()
    {
        $path = realpath(__DIR__.'/../../_data/plank.png');
        $file = new TemporaryFile(file_get_contents($path), 'plank.jpg');

        $this->assertEquals('jpg', $file->getOriginalExtension());
    }

    public function test_it_opens_the_file_resource()
    {
        $path = realpath(__DIR__.'/../../_data/plank.png');
        $file = new TemporaryFile(file_get_contents($path), 'plank.png');

        $this->assertInternalType('resource', $file->open());
    }

    public function test_it_writes_contents_on_the_temporary_file()
    {
        $path = realpath(__DIR__.'/../../_data/plank.png');
        $file = new TemporaryFile(file_get_contents($path), 'plank.png');

        $file->put($file->getRealPath(), 'foo');

        $this->assertStringEqualsFile($file->getRealPath(), 'foo');
    }

    public function test_it_deletes_the_temporary_file()
    {
        $path = realpath(__DIR__.'/../../_data/plank.png');
        $file = new TemporaryFile(file_get_contents($path), 'plank.png');

        $this->assertTrue($file->delete());
        $this->assertFalse($file->isFile());
    }

    public function test_it_deletes_the_temporary_file_on_destruction()
    {
        $path = realpath(__DIR__.'/../../_data/plank.png');
        $file = new TemporaryFile(file_get_contents($path), 'plank.png');

        $tempPath = $file->getRealPath();

        unset($file);

        $this->assertFalse(file_exists($tempPath));
    }
}
