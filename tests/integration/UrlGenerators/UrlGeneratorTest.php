<?php

use Frasmage\Mediable\Exceptions\MediaUrlException;
use Frasmage\Mediable\UrlGenerators\UrlGeneratorFactory;
use Frasmage\Mediable\UrlGenerators\UrlGenerator;
use Frasmage\Mediable\Media;

class UrlGeneratorTest extends TestCase
{

    public function test_it_sets_generator_for_driver()
    {
        $factory = new UrlGeneratorFactory;
        $generator = $this->getMockClass(UrlGenerator::class);

        $media = factory(Media::class)->make(['disk' => 'uploads']);

        $factory->setGeneratorForFilesystemDriver($generator, 'local');
        $result = $factory->create($media);
        $this->assertInstanceOf($generator, $result);
    }

    public function test_it_throws_exception_for_invalid_generator()
    {
        $factory = new UrlGeneratorFactory;
        $class = $this->getMockClass(stdClass::class);
        $this->expectException(MediaUrlException::class);
        $factory->setGeneratorForFilesystemDriver($class, 'foo');
    }

    public function test_it_throws_exception_if_cant_map_to_driver()
    {
        $factory = new UrlGeneratorFactory;
        $media = factory(Media::class)->make();
        $this->expectException(MediaUrlException::class);
        $factory->create($media);
    }
}
