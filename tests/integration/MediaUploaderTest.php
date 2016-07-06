<?php

use Frasmage\Mediable\Media;
use Frasmage\Mediable\MediaUploader;
use Frasmage\Mediable\SourceAdapterFactory;
use Frasmage\Mediable\SourceAdapters\SourceAdapter;
use Frasmage\Mediable\Exceptions\MediaUploadException;
use MediaUploader as Facade;
use Illuminate\Filesystem\FilesystemManager;
use League\Flysystem\Filesystem;

class MediaUploaderTest extends TestCase
{
    public function test_it_can_be_instantiated_via_the_container()
    {
        $this->assertInstanceOf(MediaUploader::class, app('mediable.uploader'));
    }

    public function test_it_can_be_instantiated_via_facade()
    {
        $this->assertInstanceOf(MediaUploader::class, Facade::setDirectory('foo'));
    }

    public function test_it_can_determine_media_type_by_extension_and_mime()
    {
        $uploader = $this->mockUploader();
        $uploader->setTypeDefinition('foo', ['text/foo'], ['foo']);
        $uploader->setTypeDefinition('bar', ['text/foo', 'text/bar'], ['foo', 'bar']);
        $uploader->setTypeDefinition('baz', ['text/foo', 'text/baz'], ['baz']);
        $uploader->setTypeDefinition('bat', ['text/bat'], ['bat']);

        $this->assertEquals('foo', $uploader->inferMediaType('text/foo', 'foo', false), 'Double match, loose');
        $this->assertEquals('foo', $uploader->inferMediaType('text/foo', 'foo', true), 'Double match, strict');
        $this->assertEquals('bat', $uploader->inferMediaType('text/bat', 'foo', false), 'Loose should match MIME type first');
        $this->assertEquals(Media::TYPE_OTHER, $uploader->inferMediaType('text/abc', 'abc', false), 'Loose match none');
        $this->assertEquals(Media::TYPE_OTHER, $uploader->inferMediaType('text/abc', 'abc', true), 'Strict match none');
    }

    public function test_it_throws_exception_for_type_mismatch()
    {
        $uploader = $this->mockUploader();
        $uploader->setTypeDefinition('foo', ['text/foo'], ['foo']);
        $uploader->setTypeDefinition('bar', ['text/bar'], ['bar']);
        $uploader->setStrictTypeChecking(true);
        $this->expectException(MediaUploadException::class);
        $uploader->inferMediaType('text/foo', 'bar');
    }

    public function test_it_throws_exception_for_non_existent_disk()
    {
        $uploader = $this->mockUploader();
        $this->expectException(MediaUploadException::class);
        $uploader->setDisk('abc');
    }

    public function test_it_throws_exception_for_disallowed_disk()
    {
        $uploader = $this->mockUploader();
        $this->expectException(MediaUploadException::class);
        $uploader->setDisk('public');
    }

    public function test_it_can_change_model_class()
    {
        $uploader = $this->mockUploader();
        $method = $this->getPrivateMethod($uploader, 'makeModel');
        $class = $this->getMockClass(Media::class, null, [], '', true);
        $uploader->setModelClass($class);
        $this->assertInstanceOf($class, $method->invoke($uploader));
    }

    public function test_it_throw_exception_for_invalid_model()
    {
        $uploader = $this->mockUploader();
        $this->expectException(MediaUploadException::class);
        $uploader->setModelClass(stdClass::class);
    }

    public function test_it_validates_source_is_set()
    {
        $uploader = $this->mockUploader();
        $method = $this->getPrivateMethod($uploader, 'verifySource');

        $this->setExpectedException(MediaUploadException::class);
        $method->invoke($uploader);
    }

    public function test_it_validates_source_is_valid()
    {
        $uploader = $this->mockUploader();
        $method = $this->getPrivateMethod($uploader, 'verifySource');

        $source = $this->createMock(SourceAdapter::class);
        $source->method('valid')->willReturn(true);
        $uploader->fromSource($source);
        $method->invoke($uploader);

        $this->assertTrue(true);
    }

    public function test_it_validates_source_is_invalid()
    {
        $uploader = $this->mockUploader();
        $method = $this->getPrivateMethod($uploader, 'verifySource');

        $source = $this->createMock(SourceAdapter::class);
        $source->method('valid')->willReturn(false);
        $uploader->fromSource($source);

        $this->expectException(MediaUploadException::class);
        $method->invoke($uploader);
    }

    public function test_it_validates_allowed_mime_types()
    {
        $uploader = $this->mockUploader();
        $method = $this->getPrivateMethod($uploader, 'verifyMimeType');

        $this->assertEquals('text/foo', $method->invoke($uploader, 'text/foo'), 'No restrictions');

        $uploader->setAllowedMimeTypes(['text/bar']);
        $this->assertEquals('text/bar', $method->invoke($uploader, 'text/bar'), 'With Restriction');

        $this->expectException(MediaUploadException::class);
        $method->invoke($uploader, 'text/foo');
    }

    public function test_it_validates_allowed_extensions()
    {
        $uploader = $this->mockUploader();
        $method = $this->getPrivateMethod($uploader, 'verifyExtension');

        $this->assertEquals('foo', $method->invoke($uploader, 'foo'), 'No restrictions');

        $uploader->setAllowedExtensions(['bar']);
        $this->assertEquals('bar', $method->invoke($uploader, 'bar'), 'With Restriction');

        $this->expectException(MediaUploadException::class);
        $method->invoke($uploader, 'foo');
    }

    public function test_it_validates_allowed_types()
    {
        $uploader = $this->mockUploader();
        $method = $this->getPrivateMethod($uploader, 'verifyMediaType');

        $this->assertEquals('foo', $method->invoke($uploader, 'foo', 'text/foo', 'foo'), 'No restrictions');

        $uploader->setAllowedMediaTypes(['bar']);
        $this->assertEquals('bar', $method->invoke($uploader, 'bar', 'text/bar', 'bar'), 'With Restriction');

        $this->expectException(MediaUploadException::class);
        $method->invoke($uploader, 'foo', 'text/foo', 'bar');
    }

    public function test_it_can_restrict_to_known_types()
    {
        $uploader = $this->mockUploader();
        $method = $this->getPrivateMethod($uploader, 'verifyMediaType');

        $uploader->setAllowUnrecognizedTypes(true);
        $this->assertEquals(Media::TYPE_OTHER, $method->invoke($uploader, Media::TYPE_OTHER, 'text/foo', 'bar'));
        $uploader->setAllowUnrecognizedTypes(false);
        $this->expectException(MediaUploadException::class);
        $method->invoke($uploader, Media::TYPE_OTHER, 'text/foo', 'bar');
    }

    public function test_it_validates_file_size()
    {
        $uploader = $this->mockUploader();
        $uploader->setMaximumSize(2);
        $method = $this->getPrivateMethod($uploader, 'verifyFileSize');

        $this->assertEquals(1, $method->invoke($uploader, 1));
        $this->expectException(MediaUploadException::class);
        $method->invoke($uploader, 3);
    }

    public function test_it_can_disable_file_size_limits()
    {
        $uploader = $this->mockUploader();
        $uploader->setMaximumSize(0);
        $method = $this->getPrivateMethod($uploader, 'verifyFileSize');
        $this->assertEquals(99999, $method->invoke($uploader, 99999));
    }


    public function test_it_can_error_on_duplicate_files()
    {
        $uploader = $this->mockDuplicateUploader();
        $uploader->setOnDuplicateBehavior(MediaUploader::ON_DUPLICATE_ERROR);
        $method = $this->getPrivateMethod($uploader, 'verifyDestination');
        $this->setExpectedException(MediaUploadException::class);
        $method->invoke($uploader, $this->createMock(Media::class));
    }

    public function test_it_can_replace_duplicate_files()
    {
        $uploader = $this->mockDuplicateUploader();
        $uploader->setOnDuplicateBehavior(MediaUploader::ON_DUPLICATE_REPLACE);
        $method = $this->getPrivateMethod($uploader, 'verifyDestination');

        $media = factory(Media::class)->create([
            'disk' => 'tmp',
            'directory'=> '',
            'filename' => 'plank',
            'extension' => 'png'
        ]);

        $method->invoke($uploader, $media);

        $this->assertEquals(0, Media::all()->count());
    }

    public function test_it_can_increment_filename_on_duplicate_files()
    {
        $uploader = $this->mockDuplicateUploader();
        $uploader->setOnDuplicateBehavior(MediaUploader::ON_DUPLICATE_INCREMENT);
        $method = $this->getPrivateMethod($uploader, 'verifyDestination');

        $media = factory(Media::class)->create([
            'disk' => 'tmp',
            'directory'=> '',
            'filename' => 'plank',
            'extension' => 'png'
        ]);

        $method->invoke($uploader, $media);

        $this->assertEquals('plank (2)', $media->filename);
    }

    public function test_it_uploads_files()
    {
        $media = Facade::fromSource(__DIR__ . '/../_data/plank.png')
            ->toDestination('tmp', 'foo')
            ->setFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(8444, $media->size);
        $this->assertEquals('image', $media->type);
    }

    protected function mockUploader($filesystem = null, $factory = null)
    {
        return new MediaUploader(
            $filesystem ?: $this->createMock(FilesystemManager::class),
            $factory ?: $this->mockFactory(),
            []
        );
    }

    protected function mockFactory()
    {
        $factory = $this->createMock(SourceAdapterFactory::class);
        $factory->method('create')->will($this->returnArgument(0));
        return $factory;
    }

    protected function mockDuplicateUploader()
    {
        $storage = $this->createMock(Filesystem::class);
        $storage->method('has')->will($this->onConsecutiveCalls(true, true, false));
        $filesystem = $this->createMock(FilesystemManager::class);
        $filesystem->method('disk')->willReturn($storage);
        return $this->mockUploader($filesystem);
    }
}
