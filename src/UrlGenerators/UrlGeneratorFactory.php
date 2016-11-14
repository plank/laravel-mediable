<?php

namespace Plank\Mediable\UrlGenerators;

use Plank\Mediable\Exceptions\MediaUrlException;
use Plank\Mediable\Media;

/**
 * Url Generator Factory.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class UrlGeneratorFactory
{
    /**
     * map of UrlGenerator classes to use for different filesystem drivers.
     * @var array
     */
    protected $driver_generators = [];

    /**
     * Get a UrlGenerator instance for a media.
     * @param  \Plank\Mediable\Media  $media
     * @return UrlGenerator
     * @throws \Plank\Mediable\Exceptions\MediaUrlException If no generator class has been assigned for the media's disk's driver
     */
    public function create(Media $media)
    {
        $driver = $this->getDriverForDisk($media->disk);
        if (array_key_exists($driver, $this->driver_generators)) {
            $class = $this->driver_generators[$driver];

            $generator = app($class);
            $generator->setMedia($media);

            return $generator;
        }

        throw MediaUrlException::generatorNotFound($media->disk, $driver);
    }

    /**
     * Set a generator subclass to use for media on a disk with a particular driver.
     * @param string $generator
     * @param string $driver
     * @return void
     */
    public function setGeneratorForFilesystemDriver($class, $driver)
    {
        $this->validateGeneratorClass($class);
        $this->driver_generators[$driver] = $class;
    }

    /**
     * Verify that a class name is a valid generator.
     * @param  string $class
     * @return void
     * @throws \Plank\Mediable\Exceptions\MediaUrlException If class does not exist or does not implement `UrlGenerator`
     */
    protected function validateGeneratorClass($class)
    {
        if (! class_exists($class) || ! is_subclass_of($class, UrlGeneratorInterface::class)) {
            throw MediaUrlException::invalidGenerator($class);
        }
    }

    /**
     * Get the driver used by a specified disk.
     * @param  string $disk
     * @return string
     */
    protected function getDriverForDisk($disk)
    {
        return config("filesystems.disks.{$disk}.driver");
    }
}
