<?php

use Frasmage\Mediable\MediaUploader;
use MediaUploader as Facade;

class MediaUploaderTest extends TestCase{
	public function test_it_can_be_instantiated_via_the_container(){
		$this->assertInstanceOf(MediaUploader::class, app('mediable.uploader'));
	}

	public function test_it_can_be_instantiated_via_facade(){
		$this->assertInstanceOf(MediaUploader::class, Facade::setDirectory('foo'));
	}
	
}