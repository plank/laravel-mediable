<?php

use Plank\Mediable\Media;
use Plank\Mediable\MediaUploader;
use Plank\Mediable\Stream;
use Plank\Mediable\SourceAdapters\SourceAdapterInterface;
use Plank\Mediable\Exceptions\MediaUpload\FileSizeException;
use Plank\Mediable\Exceptions\MediaUpload\FileExistsException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotFoundException;
use Plank\Mediable\Exceptions\MediaUpload\ForbiddenException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\MediaUploaderFacade as Facade;

class MediaUploaderTest extends TestCase
{
    public function test_it_can_be_instantiated_via_the_container()
    {
        $this->assertInstanceOf(MediaUploader::class, app('mediable.uploader'));
    }

    public function test_it_can_be_instantiated_via_facade()
    {
        $this->assertInstanceOf(MediaUploader::class, Facade::getFacadeRoot());
    }

    public function test_it_can_set_on_duplicate_behavior_via_facade()
    {
        $uploader = Facade::onDuplicateError();
        $this->assertEquals(MediaUploader::ON_DUPLICATE_ERROR, $uploader->getOnDuplicateBehavior());

        $uploader = Facade::onDuplicateIncrement();
        $this->assertEquals(MediaUploader::ON_DUPLICATE_INCREMENT, $uploader->getOnDuplicateBehavior());

        $uploader = Facade::onDuplicateReplace();
        $this->assertEquals(MediaUploader::ON_DUPLICATE_REPLACE, $uploader->getOnDuplicateBehavior());

        $uploader = Facade::onDuplicateDelete();
        $this->assertEquals(MediaUploader::ON_DUPLICATE_DELETE, $uploader->getOnDuplicateBehavior());
    }

    public function test_it_can_determine_media_type_by_extension_and_mime()
    {
        $uploader = $this->getUploader();
        $uploader->setTypeDefinition('foo', ['text/foo'], ['foo']);
        $uploader->setTypeDefinition('bar', ['text/foo', 'text/bar'], ['foo', 'bar']);
        $uploader->setTypeDefinition('baz', ['text/foo', 'text/baz'], ['baz']);
        $uploader->setTypeDefinition('bat', ['text/bat'], ['bat']);
        $uploader->setAllowUnrecognizedTypes(true);

        $this->assertEquals('foo', $uploader->inferAggregateType('text/foo', 'foo', false), 'Double match, loose');
        $this->assertEquals('foo', $uploader->inferAggregateType('text/foo', 'foo', true), 'Double match, strict');
        $this->assertEquals('bat', $uploader->inferAggregateType('text/bat', 'foo', false), 'Loose should match MIME type first');
        $this->assertEquals(Media::TYPE_OTHER, $uploader->inferAggregateType('text/abc', 'abc', false), 'Loose match none');
        $this->assertEquals(Media::TYPE_OTHER, $uploader->inferAggregateType('text/abc', 'abc', true), 'Strict match none');
    }

    public function test_it_throws_exception_for_type_mismatch()
    {
        $uploader = $this->getUploader();
        $uploader->setTypeDefinition('foo', ['text/foo'], ['foo']);
        $uploader->setTypeDefinition('bar', ['text/bar'], ['bar']);
        $uploader->setStrictTypeChecking(true);
        $this->expectException(FileNotSupportedException::class);
        $uploader->inferAggregateType('text/foo', 'bar');
    }

    public function test_it_validates_allowed_types()
    {
        $uploader = $this->getUploader();
        $uploader->setTypeDefinition('foo', ['text/foo'], ['foo']);
        $uploader->setTypeDefinition('bar', ['text/bar'], ['bar']);

        $this->assertEquals('foo', $uploader->inferAggregateType('text/foo', 'foo'), 'No restrictions');

        $uploader->setAllowedAggregateTypes(['bar']);
        $this->assertEquals('bar', $uploader->inferAggregateType('text/bar', 'bar'), 'With Restriction');

        $this->expectException(FileNotSupportedException::class);
        $uploader->inferAggregateType('text/foo', 'bar');
    }

    public function test_it_can_restrict_to_known_types()
    {
        $uploader = $this->getUploader();

        $uploader->setAllowUnrecognizedTypes(true);
        $this->assertEquals(Media::TYPE_OTHER, $uploader->inferAggregateType('text/foo', 'bar'));
        $uploader->setAllowUnrecognizedTypes(false);
        $this->expectException(FileNotSupportedException::class);
        $uploader->inferAggregateType('text/foo', 'bar');
    }

    public function test_it_throws_exception_for_non_existent_disk()
    {
        $uploader = $this->getUploader();
        $this->expectException(ConfigurationException::class);
        $uploader->toDisk('abc');
    }

    public function test_it_throws_exception_for_disallowed_disk()
    {
        $uploader = $this->getUploader();
        config()->set('filesystems.disks.foo', []);
        $this->expectException(ForbiddenException::class);
        $uploader->toDisk('foo');
    }

    public function test_it_can_change_model_class()
    {
        $uploader = $this->getUploader();
        $method = $this->getPrivateMethod($uploader, 'makeModel');
        $uploader->setModelClass(MediaSubclass::class);
        $this->assertInstanceOf(MediaSubclass::class, $method->invoke($uploader));
    }

    public function test_it_throw_exception_for_invalid_model()
    {
        $uploader = $this->getUploader();
        $this->expectException(ConfigurationException::class);
        $uploader->setModelClass(stdClass::class);
    }

    public function test_it_validates_source_is_set()
    {
        $uploader = $this->getUploader();
        $method = $this->getPrivateMethod($uploader, 'verifySource');

        $this->expectException(ConfigurationException::class);
        $method->invoke($uploader);
    }

    public function test_it_validates_source_is_valid()
    {
        $uploader = $this->getUploader();
        $method = $this->getPrivateMethod($uploader, 'verifySource');

        $source = $this->createMock(SourceAdapterInterface::class);
        $source->method('valid')->willReturn(true);
        $uploader->fromSource($source);
        $method->invoke($uploader);

        $this->assertTrue(true);
    }

    public function test_it_validates_source_is_invalid()
    {
        $uploader = $this->getUploader();
        $method = $this->getPrivateMethod($uploader, 'verifySource');

        $source = $this->createMock(SourceAdapterInterface::class);
        $source->method('valid')->willReturn(false);
        $source->method('path')->willReturn('');
        $uploader->fromSource($source);

        $this->expectException(FileNotFoundException::class);
        $method->invoke($uploader);
    }

    public function test_it_validates_allowed_mime_types()
    {
        $uploader = $this->getUploader();
        $method = $this->getPrivateMethod($uploader, 'verifyMimeType');

        $this->assertEquals('text/foo', $method->invoke($uploader, 'text/foo'), 'No restrictions');

        $uploader->setAllowedMimeTypes(['text/bar']);
        $this->assertEquals('text/bar', $method->invoke($uploader, 'text/bar'), 'With Restriction');

        $this->expectException(FileNotSupportedException::class);
        $method->invoke($uploader, 'text/foo');
    }

    public function test_it_validates_allowed_extensions()
    {
        $uploader = $this->getUploader();
        $method = $this->getPrivateMethod($uploader, 'verifyExtension');

        $this->assertEquals('foo', $method->invoke($uploader, 'foo'), 'No restrictions');

        $uploader->setAllowedExtensions(['bar']);
        $this->assertEquals('bar', $method->invoke($uploader, 'bar'), 'With Restriction');

        $this->expectException(FileNotSupportedException::class);
        $method->invoke($uploader, 'foo');
    }

    public function test_it_validates_file_size()
    {
        $uploader = $this->getUploader();
        $uploader->setMaximumSize(2);
        $method = $this->getPrivateMethod($uploader, 'verifyFileSize');

        $this->assertEquals(1, $method->invoke($uploader, 1));
        $this->expectException(FileSizeException::class);
        $method->invoke($uploader, 3);
    }

    public function test_it_can_disable_file_size_limits()
    {
        $uploader = $this->getUploader();
        $uploader->setMaximumSize(0);
        $method = $this->getPrivateMethod($uploader, 'verifyFileSize');
        $this->assertEquals(99999, $method->invoke($uploader, 99999));
    }


    public function test_it_can_error_on_duplicate_files()
    {
        $uploader = $this->getUploader();
        $uploader->setOnDuplicateBehavior(MediaUploader::ON_DUPLICATE_ERROR);
        $method = $this->getPrivateMethod($uploader, 'handleDuplicate');
        $this->expectException(FileExistsException::class);
        $method->invoke($uploader, new Media);
    }

    public function test_it_sets_file_visibility()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media1 = $this->getUploader()->fromSource($this->sampleFilePath())
            ->toDestination('tmp', 'a')
            ->makePrivate()
            ->upload();

        $media2 = $this->getUploader()->fromSource($this->sampleFilePath())
            ->toDestination('tmp', 'b')
            ->makePrivate()
            ->makePublic()
            ->upload();

        $this->assertFalse($media1->isVisible());

        $this->assertTrue($media2->isVisible());
    }

    public function test_it_can_delete_duplicate_files()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $uploader = $this->getUploader()->onDuplicateDelete();
        $method = $this->getPrivateMethod($uploader, 'handleDuplicate');

        $media = factory(Media::class)->create([
            'disk' => 'tmp',
            'directory'=> '',
            'filename' => 'plank',
            'extension' => 'png'
        ]);
        $this->seedFileForMedia($media, $this->sampleFilePath());

        $method->invoke($uploader, $media);

        $this->assertEquals(0, Media::all()->count());
        $this->assertFalse(file_exists($media->getAbsolutePath()));
    }

    public function test_it_can_replace_duplicate_files()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = factory(Media::class)->create([
            'disk' => 'tmp',
            'directory'=> '',
            'filename' => 'plank',
            'extension' => 'png',
            'aggregate_type' => 'bar'
        ]);
        $this->seedFileForMedia($media, $this->sampleFile());

        $result = Facade::fromSource(__DIR__.'/../_data/plank.png')
            ->setOnDuplicateBehavior(MediaUploader::ON_DUPLICATE_REPLACE)
            ->toDestination('tmp', '')
            ->upload();
        $media = $media->fresh();

        $this->assertEquals($media->getKey(), $result->getKey());
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_can_increment_filename_on_duplicate_files()
    {
        $uploader = $this->getUploader()->onDuplicateIncrement();
        $method = $this->getPrivateMethod($uploader, 'handleDuplicate');

        $media = factory(Media::class)->make([
            'disk' => 'tmp',
            'directory'=> '',
            'filename' => 'plank',
            'extension' => 'png'
        ]);

        $method->invoke($uploader, $media);

        $this->assertEquals('plank (1)', $media->filename);
    }

    public function test_it_uploads_files()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = Facade::fromSource($this->sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(7173, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_string_contents()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $string = file_get_contents(__DIR__.'/../_data/plank.png');

        $media = Facade::fromString($string)
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(7173, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_file_stream_contents()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $resource = fopen(realpath(__DIR__.'/../_data/plank.png'), 'r');

        $media = Facade::fromSource($resource)
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(7173, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_http_stream_contents()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $resource = fopen('https://www.plankdesign.com/externaluse/plank.png', 'r');

        $media = Facade::fromSource($resource)
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(7173, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_stream_objects()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $stream = new Stream(fopen('https://www.plankdesign.com/externaluse/plank.png', 'r'));

        $media = Facade::fromSource($stream)
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(7173, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_existing_files()
    {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = factory(Media::class)->make([
            'disk' => 'tmp',
            'directory' => 'foo',
            'filename' => 'bar',
            'extension' => 'png',
            'mime_type' => 'image/png'
        ]);
        $this->seedFileForMedia($media, $this->sampleFile());

        $media = Facade::importPath('tmp', 'foo/bar.png');
        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(7173, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_updates_existing_media()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = factory(Media::class)->create([
            'disk' => 'tmp',
            'extension' => 'png',
            'mime_type' => 'video/mpeg',
            'aggregate_type' => 'video',
            'size' => 999,
        ]);
        $this->seedFileForMedia($media, $this->sampleFile());

        $result = Facade::update($media);

        $this->assertTrue($result);
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals('image', $media->aggregate_type);
        $this->assertEquals(7173, $media->size);
    }

    public function test_it_replaces_existing_media()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = factory(Media::class)->create([
            'disk' => 'tmp',
            'extension' => 'png',
            'size' => 999
        ]);
        $this->seedFileForMedia($media, $this->sampleFile());

        $result = Facade::fromSource($this->alternateFilePath())->replace($media);
        $media = $media->fresh();

        $this->assertEquals($result->getKey(), $media->getKey());
        $this->assertEquals(4181, $media->size);
    }

    public function test_it_throws_exception_when_importing_missing_file()
    {
        $this->expectException(FileNotFoundException::class);
        Facade::import('tmp', 'non', 'existing', 'jpg');
    }

    public function test_it_use_hash_for_filename()
    {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = Facade::fromSource($this->sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useHashForFilename()
            ->upload();

        $this->assertEquals('3ef5e70366086147c2695325d79a25cc', $media->filename);
    }

    public function test_it_can_revert_to_original_filename()
    {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = Facade::fromSource($this->sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useHashForFilename()
            ->useOriginalFilename()
            ->upload();

        $this->assertEquals('plank', $media->filename);
    }

    protected function getUploader()
    {
        return app('mediable.uploader');
    }
}
