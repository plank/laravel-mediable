<?php

namespace Frasmage\Mediable\UrlGenerators;

use Frasmage\Mediable\Exceptions\MediaUrlException;
use Frasmage\Mediable\Media;

class UrlGeneratorFactory{
	protected $driver_generators = [];

	public function create(Media $media){
		$driver = $this->getDriverForDisk($media->disk);
		if(array_key_exists($driver, $this->driver_generators)){
			$class = $this->driver_generators[$driver];

			$generator = app($class);
            $generator->setMedia($media);
            return $generator;
		}

		throw MediaUrlException::generatorNotFound($driver);
	}

	public function setGeneratorForFilesystemDriver($generator, $driver){
		$this->validateGeneratorClass($generator);
		$this->driver_generators[$driver] = $generator;
	}

	protected function validateGeneratorClass($class){
		if(!class_exists($class) || !is_subclass_of($class, UrlGenerator::class)){
			throw MediaUrlException::invalidGenerator($class);
		}
	}

	protected function getDriverForDisk($disk){
		return config("filesystems.disks.{$disk}.driver");
	}
}
