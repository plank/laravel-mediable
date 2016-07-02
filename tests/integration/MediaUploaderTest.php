<?php

use Frasmage\Mediable\Media;
use Frasmage\Mediable\MediaUploader;
use Frasmage\Mediable\SourceAdapterFactory;
use Frasmage\Mediable\Exceptions\MediaUploadException;
use MediaUploader as Facade;
use Illuminate\Filesystem\FilesystemManager;

class MediaUploaderTest extends TestCase{
	public function test_it_can_be_instantiated_via_the_container(){
		$this->assertInstanceOf(MediaUploader::class, app('mediable.uploader'));
	}

	public function test_it_can_be_instantiated_via_facade(){
		$this->assertInstanceOf(MediaUploader::class, Facade::setDirectory('foo'));
	}

	public function test_it_can_determine_media_type_by_extension_and_mime(){
		$uploader = $this->mockUploader();
		$uploader->setTypeDefinition('foo', ['text/foo'], ['foo']);
		$uploader->setTypeDefinition('bar', ['text/foo', 'text/bar'], ['foo', 'bar']);
		$uploader->setTypeDefinition('baz', ['text/foo', 'text/baz'], ['baz']);
		$uploader->setTypeDefinition('bat', ['text/bat'], ['bat']);

		$this->assertEquals('foo', $uploader->inferMediaType('text/foo', 'foo', false));
		$this->assertEquals('foo', $uploader->inferMediaType('text/foo', 'foo', true));
		$this->assertEquals('bat', $uploader->inferMediaType('text/bat', 'foo'), false);
		$this->assertEquals('foo', $uploader->inferMediaType('text/foo', 'bat'), false);
		$this->assertEquals('foo', $uploader->inferMediaType('text/foo', 'bat'), false);
		$this->assertEquals(Media::TYPE_OTHER, $uploader->inferMediaType('text/abc', 'abc', false));
		$this->assertEquals(Media::TYPE_OTHER, $uploader->inferMediaType('text/abc', 'abc', true));
	}

	public function test_it_throws_exception_for_type_mismatch(){
		$uploader = $this->mockUploader();
		$uploader->setTypeDefinition('foo', ['text/foo'], ['foo']);
		$uploader->setTypeDefinition('bar', ['text/bar'], ['bar']);
		$this->expectException(MediaUploadException::class);
		$uploader->inferMediaType('text/foo', 'bar', true);
	}

	protected function mockUploader(){
		$filesystem = $this->mockFilesystem()->getMock();
		$factory = $this->mockFactory()->getMock();
		return new MediaUploader($filesystem, $factory, []);
	}

	protected function mockFilesystem(){
		return $this->getMockBuilder(FilesystemManager::class)->setConstructorArgs([app()]);
	}

	protected function mockFactory(){
		return $this->getMockBuilder(SourceAdapterFactory::class);
	}
}