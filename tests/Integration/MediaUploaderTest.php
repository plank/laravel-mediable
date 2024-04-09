<?php

namespace Plank\Mediable\Tests\Integration;

use GuzzleHttp\Psr7\Utils;
use Intervention\Image\Image;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\Exceptions\MediaUpload\FileExistsException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotFoundException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException;
use Plank\Mediable\Exceptions\MediaUpload\FileSizeException;
use Plank\Mediable\Exceptions\MediaUpload\ForbiddenException;
use Plank\Mediable\Exceptions\MediaUpload\InvalidHashException;
use Plank\Mediable\ImageManipulation;
use Plank\Mediable\ImageManipulator;
use Plank\Mediable\Media;
use Plank\Mediable\MediaUploader;
use Plank\Mediable\Facades\MediaUploader as Facade;
use Plank\Mediable\SourceAdapters\SourceAdapterInterface;
use Plank\Mediable\Tests\Mocks\MediaSubclass;
use Plank\Mediable\Tests\TestCase;
use stdClass;

class MediaUploaderTest extends TestCase
{
    public function test_it_can_be_instantiated_via_the_container(): void
    {
        $this->assertInstanceOf(MediaUploader::class, app('mediable.uploader'));
    }

    public function test_it_can_be_instantiated_via_facade(): void
    {
        $this->assertInstanceOf(MediaUploader::class, Facade::getFacadeRoot());
    }

    public function test_facade_instantiates_unique_instances(): void
    {
        /** @var MediaUploader $uploader1 */
        $uploader1 = Facade::getFacadeRoot();
        $uploader1->setAllowedAggregateTypes(['image', 'vector']);

        /** @var MediaUploader $uploader2 */
        $uploader2 = Facade::getFacadeRoot();
        $uploader2->setAllowedAggregateTypes(['archive']);

        $config = $this->getPrivateProperty($uploader1, 'config');
        $config->setAccessible(true);
        $this->assertNotEquals(
            $config->getValue($uploader1),
            $config->getValue($uploader2)
        );
    }

    public function test_facade_is_mockable(): void
    {
        Facade::shouldReceive('upload')->once();
        Facade::upload();
    }

    public function test_it_can_set_on_duplicate_behavior_via_facade(): void
    {
        $uploader = Facade::onDuplicateError();
        $this->assertEquals(
            MediaUploader::ON_DUPLICATE_ERROR,
            $uploader->getOnDuplicateBehavior()
        );

        $uploader = Facade::onDuplicateIncrement();
        $this->assertEquals(
            MediaUploader::ON_DUPLICATE_INCREMENT,
            $uploader->getOnDuplicateBehavior()
        );

        $uploader = Facade::onDuplicateReplace();
        $this->assertEquals(
            MediaUploader::ON_DUPLICATE_REPLACE,
            $uploader->getOnDuplicateBehavior()
        );

        $uploader = Facade::onDuplicateReplaceWithVariants();
        $this->assertEquals(
            MediaUploader::ON_DUPLICATE_REPLACE_WITH_VARIANTS,
            $uploader->getOnDuplicateBehavior()
        );

        $uploader = Facade::onDuplicateUpdate();
        $this->assertEquals(
            MediaUploader::ON_DUPLICATE_UPDATE,
            $uploader->getOnDuplicateBehavior()
        );
    }

    public function test_it_sets_options(): void
    {
        $uploader = $this->getUploader();
        $this->assertEquals(
            ['visibility' => 'public'],
            $uploader->getOptions()
        );

        $return = $uploader->withOptions(['foo' => 'bar']);
        $this->assertSame($return, $uploader);
        $this->assertEquals(
            ['visibility' => 'public', 'foo' => 'bar'],
            $uploader->getOptions()
        );
        $uploader->withOptions(['visibility' => 'private']);
        $this->assertEquals(
            ['visibility' => 'private'],
            $uploader->getOptions()
        );
    }

    public function test_it_can_determine_media_type_by_extension_and_mime(): void
    {
        $uploader = $this->getUploader();
        $uploader->setTypeDefinition('foo', ['text/foo'], ['foo']);
        $uploader->setTypeDefinition('bar', ['text/foo', 'text/bar'], ['foo', 'bar']);
        $uploader->setTypeDefinition('baz', ['text/foo', 'text/baz'], ['baz']);
        $uploader->setTypeDefinition('bat', ['text/bat'], ['bat']);
        $uploader->setAllowUnrecognizedTypes(true);

        $this->assertEquals(
            'foo',
            $uploader->inferAggregateType('text/foo', 'foo'),
            'Double match'
        );
        $this->assertEquals(
            'bat',
            $uploader->inferAggregateType('text/bat', 'foo'),
            'Loose should match MIME type first'
        );
        $this->assertEquals(
            Media::TYPE_OTHER,
            $uploader->inferAggregateType('text/abc', 'abc'),
            'Loose match none'
        );
    }

    public function test_it_throws_exception_for_type_mismatch(): void
    {
        $uploader = $this->getUploader();
        $uploader->setTypeDefinition('foo', ['text/foo'], ['foo']);
        $uploader->setTypeDefinition('bar', ['text/bar'], ['bar']);
        $uploader->setStrictTypeChecking(true);
        $this->expectException(FileNotSupportedException::class);
        $uploader->inferAggregateType('text/foo', 'bar');
    }

    public function test_it_validates_allowed_types(): void
    {
        $uploader = $this->getUploader();
        $uploader->setTypeDefinition('foo', ['text/foo'], ['foo']);
        $uploader->setTypeDefinition('bar', ['text/bar'], ['bar']);

        $this->assertEquals(
            'foo',
            $uploader->inferAggregateType('text/foo', 'foo'),
            'No restrictions'
        );

        $uploader->setAllowedAggregateTypes(['bar']);
        $this->assertEquals(
            'bar',
            $uploader->inferAggregateType('text/bar', 'bar'),
            'With Restriction'
        );

        $this->expectException(FileNotSupportedException::class);
        $uploader->inferAggregateType('text/foo', 'bar');
    }

    public function test_it_infers_type_case_insensitive(): void
    {
        $uploader = $this->getUploader();
        $uploader->setTypeDefinition('foo', ['TeXT/foo'], ['FOo']);

        $this->assertEquals(
            'foo',
            $uploader->inferAggregateType('tExt/fOo', 'foO'),
        );
    }

    public function test_it_can_restrict_to_known_types(): void
    {
        $uploader = $this->getUploader();

        $uploader->setAllowUnrecognizedTypes(true);
        $this->assertEquals(
            Media::TYPE_OTHER,
            $uploader->inferAggregateType('text/foo', 'bar')
        );
        $uploader->setAllowUnrecognizedTypes(false);
        $this->expectException(FileNotSupportedException::class);
        $uploader->inferAggregateType('text/foo', 'bar');
    }

    public function test_it_throws_exception_for_non_existent_disk(): void
    {
        $uploader = $this->getUploader();
        $this->expectException(ConfigurationException::class);
        $uploader->toDisk('abc');
    }

    public function test_it_throws_exception_for_disallowed_disk(): void
    {
        $uploader = $this->getUploader();
        config()->set('filesystems.disks.foo', []);
        $this->expectException(ForbiddenException::class);
        $uploader->toDisk('foo');
    }

    public function test_it_can_change_model_class(): void
    {
        $uploader = $this->getUploader();
        $method = $this->getPrivateMethod($uploader, 'makeModel');
        $uploader->setModelClass(MediaSubclass::class);
        $this->assertInstanceOf(MediaSubclass::class, $method->invoke($uploader));
    }

    public function test_it_throw_exception_for_invalid_model(): void
    {
        $uploader = $this->getUploader();
        $this->expectException(ConfigurationException::class);
        $uploader->setModelClass(stdClass::class);
    }

    public function test_it_validates_source_is_set(): void
    {
        $uploader = $this->getUploader();
        $method = $this->getPrivateMethod($uploader, 'verifySource');

        $this->expectException(ConfigurationException::class);
        $method->invoke($uploader);
    }

    public function test_it_validates_allowed_mime_types(): void
    {
        $uploader = $this->getUploader();
        $method = $this->getPrivateMethod($uploader, 'verifyMimeType');

        $this->assertEquals(
            'text/foo',
            $method->invoke($uploader, 'text/foo'),
            'No restrictions'
        );

        $uploader->setAllowedMimeTypes(['text/bar']);
        $this->assertEquals(
            'text/bar',
            $method->invoke($uploader, 'text/bar'),
            'With Restriction'
        );

        $this->expectException(FileNotSupportedException::class);
        $method->invoke($uploader, 'text/foo');
    }

    public function test_it_validates_allowed_extensions(): void
    {
        $uploader = $this->getUploader();
        $method = $this->getPrivateMethod($uploader, 'verifyExtension');

        $this->assertEquals('foo', $method->invoke($uploader, 'foo'), 'No restrictions');

        $uploader->setAllowedExtensions(['bar']);
        $this->assertEquals('bar', $method->invoke($uploader, 'bar'), 'With Restriction');

        $this->expectException(FileNotSupportedException::class);
        $method->invoke($uploader, 'foo');
    }

    public function test_it_validates_file_size(): void
    {
        $uploader = $this->getUploader();
        $uploader->setMaximumSize(2);
        $method = $this->getPrivateMethod($uploader, 'verifyFileSize');

        $this->assertEquals(1, $method->invoke($uploader, 1));
        $this->expectException(FileSizeException::class);
        $method->invoke($uploader, 3);
    }

    public function test_it_can_disable_file_size_limits(): void
    {
        $uploader = $this->getUploader();
        $uploader->setMaximumSize(0);
        $method = $this->getPrivateMethod($uploader, 'verifyFileSize');
        $this->assertEquals(99999, $method->invoke($uploader, 99999));
    }

    public function test_it_can_error_on_duplicate_files(): void
    {
        $uploader = $this->getUploader();
        $uploader->setOnDuplicateBehavior(MediaUploader::ON_DUPLICATE_ERROR);
        $method = $this->getPrivateMethod($uploader, 'handleDuplicate');
        $this->expectException(FileExistsException::class);
        $method->invoke($uploader, new Media);
    }

    public function test_it_sets_file_visibility(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media1 = $this->getUploader()->fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'a')
            ->upload();
        $this->assertFalse($media1->isVisible());

        $media2 = $this->getUploader()->fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'b')
            ->makePrivate()
            ->upload();
        $this->assertFalse($media2->isVisible());

        $media3 = $this->getUploader()->fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'c')
            ->makePrivate()
            ->makePublic()
            ->upload();
        $this->assertTrue($media3->isVisible());

        $media1 = $this->getUploader()->fromSource(TestCase::sampleFilePath())
            ->toDestination('novisibility', 'a')
            ->upload();
        $this->assertTrue($media1->isVisible());
    }

    public function test_it_can_replace_duplicate_files(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $uploader = $this->getUploader()->onDuplicateReplace();
        $method = $this->getPrivateMethod($uploader, 'handleDuplicate');

        $media = $this->createMedia(
            [
                'id' => 66,
                'disk' => 'tmp',
                'directory' => '',
                'filename' => 'plank',
                'extension' => 'png'
            ]
        );
        $variant = $this->createMedia(
            [
                'id' => 77,
                'disk' => 'tmp',
                'directory' => '',
                'filename' => 'plank-variant',
                'extension' => 'png',
                'original_media_id' => $media->getKey()
            ]
        );
        $this->seedFileForMedia($media, TestCase::sampleFilePath());
        $this->seedFileForMedia($variant, TestCase::sampleFilePath());

        $method->invoke($uploader, $media);

        $this->assertEquals([77], Media::all()->modelKeys());
        $this->assertFalse(file_exists($media->getAbsolutePath()));
        $this->assertTrue(file_exists($variant->getAbsolutePath()));
    }

    public function test_it_can_replace_duplicate_files_and_variants(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $uploader = $this->getUploader()->onDuplicateReplaceWithVariants();
        $method = $this->getPrivateMethod($uploader, 'handleDuplicate');

        $media = $this->createMedia(
            [
                'disk' => 'tmp',
                'directory' => '',
                'filename' => 'plank',
                'extension' => 'png'
            ]
        );
        $variant = $this->createMedia(
            [
                'disk' => 'tmp',
                'directory' => '',
                'filename' => 'plank-variant',
                'extension' => 'png',
                'original_media_id' => $media->getKey()
            ]
        );
        $this->seedFileForMedia($media, TestCase::sampleFilePath());
        $this->seedFileForMedia($variant, TestCase::sampleFilePath());

        $method->invoke($uploader, $media);

        $this->assertEquals(0, Media::all()->count());
        $this->assertFalse(file_exists($media->getAbsolutePath()));
        $this->assertFalse(file_exists($variant->getAbsolutePath()));
    }

    public function test_it_can_update_duplicate_files(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = $this->createMedia(
            [
                'disk' => 'tmp',
                'directory' => '',
                'filename' => 'plank',
                'extension' => 'png',
                'aggregate_type' => 'bar'
            ]
        );

        $this->seedFileForMedia($media, fopen(TestCase::sampleFilePath(), 'r'));

        $creaetdAt = $media->created_at;
        $updatedAt = $media->updated_at;
        sleep(1); // required to check the update time is different

        $result = Facade::fromSource(TestCase::sampleFilePath())
            ->onDuplicateUpdate()
            ->toDestination('tmp', '')->upload();

        $media = $media->fresh();
        $this->assertEquals($media->created_at, $creaetdAt);
        $this->assertNotEquals($media->updated_at, $updatedAt);
        $this->assertEquals($media->getKey(), $result->getKey());
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_can_update_duplicate_files_when_model_not_found(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = $this->makeMedia(
            [
                'disk' => 'tmp',
                'directory' => '',
                'filename' => 'plank',
                'extension' => 'png',
                'aggregate_type' => 'bar'
            ]
        );

        $this->seedFileForMedia($media, 'foo');

        $result = Facade::fromSource(TestCase::sampleFilePath())
            ->onDuplicateUpdate()
            ->toDestination('tmp', '')->upload();

        $this->assertEquals(
            file_get_contents(TestCase::sampleFilePath()),
            file_get_contents($result->getAbsolutePath())
        );
    }

    public function test_it_can_increment_filename_on_duplicate_files(): void
    {
        $uploader = $this->getUploader()->onDuplicateIncrement();
        $method = $this->getPrivateMethod($uploader, 'handleDuplicate');

        $media = factory(Media::class)->make(
            [
                'disk' => 'tmp',
                'directory' => '',
                'filename' => 'duplicate',
                'extension' => 'png'
            ]
        );
        $this->seedFileForMedia($media);

        $method->invoke($uploader, $media);

        $this->assertEquals('duplicate-1', $media->filename);
    }

    public function test_it_uploads_files(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = Facade::fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(self::TEST_FILE_SIZE, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_string_contents(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $string = file_get_contents(TestCase::sampleFilePath());

        $media = Facade::fromString($string)
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(self::TEST_FILE_SIZE, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_file_stream_contents(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $resource = fopen(realpath(TestCase::sampleFilePath()), 'r');

        $media = Facade::fromSource($resource)
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(self::TEST_FILE_SIZE, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_http_stream_contents(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $resource = fopen(TestCase::remoteFilePath(), 'r');

        $media = Facade::fromSource($resource)
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(self::TEST_FILE_SIZE, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_stream_objects(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $stream = Utils::streamFor(fopen(TestCase::remoteFilePath(), 'r'));

        $media = Facade::fromSource($stream)
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(self::TEST_FILE_SIZE, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_existing_files(): void
    {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = factory(Media::class)->make(
            [
                'disk' => 'tmp',
                'directory' => 'foo',
                'filename' => 'bar',
                'extension' => 'png',
                'mime_type' => 'image/png'
            ]
        );
        $this->seedFileForMedia($media, $this->sampleFile());

        $media = Facade::importPath('tmp', 'foo/bar.png');
        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(self::TEST_FILE_SIZE, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_imports_existing_files_with_uppercase(): void
    {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = factory(Media::class)->make(
            [
                'disk' => 'tmp',
                'directory' => 'foo',
                'filename' => 'bar',
                'extension' => 'PNG',
                'mime_type' => 'image/png'
            ]
        );
        $this->seedFileForMedia($media, $this->sampleFile());

        $media = Facade::importPath('tmp', 'foo/bar.PNG');
        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.PNG', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(self::TEST_FILE_SIZE, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
    }

    public function test_it_updates_existing_media(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = $this->createMedia(
            [
                'disk' => 'tmp',
                'extension' => 'png',
                'mime_type' => 'video/mpeg',
                'aggregate_type' => 'video',
                'size' => 999,
            ]
        );
        $this->seedFileForMedia($media, $this->sampleFile());

        $result = Facade::update($media);

        $this->assertTrue($result);
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals('image', $media->aggregate_type);
        $this->assertEquals(self::TEST_FILE_SIZE, $media->size);
    }

    public function test_it_replaces_existing_media(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = $this->createMedia(
            [
                'disk' => 'tmp',
                'extension' => 'png',
                'size' => 999
            ]
        );
        $this->seedFileForMedia($media, $this->sampleFile());

        $result = Facade::fromSource(TestCase::alternateFilePath())->replace($media);
        $media = $media->fresh();

        $this->assertEquals($result->getKey(), $media->getKey());
        $this->assertEquals(4181, $media->size);
    }

    public function test_it_throws_exception_when_importing_missing_file(): void
    {
        $this->expectException(FileNotFoundException::class);
        Facade::import('tmp', 'non', 'existing', 'jpg');
    }

    public function test_it_use_hash_for_filename(): void
    {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = Facade::fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useHashForFilename()
            ->upload();

        $this->assertEquals('3ef5e70366086147c2695325d79a25cc', $media->filename);
    }

    public function test_it_use_arbitrary_hash_algo_for_filename(): void
    {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = Facade::fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useHashForFilename('sha1')
            ->upload();

        $this->assertEquals('5e96e1fa58067853219c4cb6d3c1ce01cc5cc8ce', $media->filename);
    }

    public function test_it_uploads_files_with_altered_model(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = Facade::fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->beforeSave(
                function ($model) {
                    $model->id = 9876;
                }
            )
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(self::TEST_FILE_SIZE, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
        $this->assertEquals(9876, $media->id);
    }

    public function test_it_uploads_files_with_altered_destination(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = $this->createMedia(
            [
                'disk' => 'tmp',
                'directory' => 'baz',
                'filename' => 'buzz',
                'extension' => 'png',
            ]
        );

        $this->seedFileForMedia($media, fopen(TestCase::alternateFilePath(), 'r'));

        $media = Facade::fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->onDuplicateReplace()
            ->beforeSave(
                function (Media $model) {
                    $model->directory = 'baz';
                    $model->filename = 'buzz';
                }
            )
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('baz/buzz.png', $media->getDiskPath());
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(self::TEST_FILE_SIZE, $media->size);
        $this->assertEquals('image', $media->aggregate_type);
        $this->assertEquals(
            file_get_contents(TestCase::sampleFilePath()),
            $media->contents()
        );
    }

    public function test_it_applies_alt(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = Facade::fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->withAltAttribute('This is an alt text')
            ->upload();

        $this->assertEquals('This is an alt text', $media->alt);
    }

    public function test_it_applies_alt_to_existing_media(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = $this->createMedia(
            [
                'disk' => 'tmp',
                'directory' => 'foo',
                'filename' => 'bar',
                'extension' => 'png',
                'mime_type' => 'image/png',
                'size' => 999,
            ]
        );
        $this->seedFileForMedia($media, $this->sampleFile());

        $result = Facade::fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->withAltAttribute('This is an alt text')
            ->replace($media);

        $this->assertEquals('This is an alt text', $result->alt);
    }

    public function test_it_manipulates_images(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $manipulation = ImageManipulation::make(
            function (Image $image) {
                $image->resize(16, 16);
            }
        )->outputJpegFormat()
        ->noOptimization();

        app(ImageManipulator::class)->defineVariant(
            'foo',
            $manipulation
        );

        $media = Facade::fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->applyImageManipulation('foo')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.jpg', $media->getDiskPath());
        $this->assertEquals('image/jpeg', $media->mime_type);
        $this->assertEquals('image', $media->aggregate_type);
        $this->assertTrue(
            $media->size <= 951, // intervention/image >=3.0,
            "got size {$media->size}"
        );
    }

    public function test_it_ignores_manipulations_for_non_images(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $callback = $this->getMockCallable();
        $callback->expects($this->never())->method('__invoke');

        $manipulation = ImageManipulation::make($callback);

        $media = Facade::fromSource("data:text/plain;base64," . base64_encode('foo'))
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->applyImageManipulation($manipulation)
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
        $this->assertEquals('tmp', $media->disk);
        $this->assertEquals('foo/bar.txt', $media->getDiskPath());
        $this->assertEquals('text/plain', $media->mime_type);
        $this->assertEquals('document', $media->aggregate_type);
        $this->assertEquals(3, $media->size);
    }

    public function test_it_validates_hashes(): void
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = Facade::fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->validateHash('3ef5e70366086147c2695325d79a25cc', 'md5')
            ->validateHash('5e96e1fa58067853219c4cb6d3c1ce01cc5cc8ce', 'sha1')
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->fileExists());
    }

    public function test_it_validates_md5_hash_failure(): void
    {
        $this->expectException(InvalidHashException::class);

        Facade::fromSource(TestCase::sampleFilePath())
            ->toDestination('tmp', 'foo')
            ->useFilename('bar')
            ->validateHash('3ef5e70366086147c2695325d79a25cc', 'md5')
            ->validateHash('abcdefabcdef', 'sha1')
            ->upload();
    }

    protected function getUploader(): MediaUploader
    {
        return app('mediable.uploader');
    }
}
