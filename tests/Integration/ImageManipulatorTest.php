<?php

namespace Plank\Mediable\Tests\Integration;

use Illuminate\Filesystem\FilesystemManager;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Plank\Mediable\Exceptions\ImageManipulationException;
use Plank\Mediable\ImageManipulation;
use Plank\Mediable\ImageManipulator;
use Plank\Mediable\Media;
use Plank\Mediable\Tests\TestCase;

class ImageManipulatorTest extends TestCase
{
    public function test_it_can_be_accessed_via_facade()
    {
        $this->assertInstanceOf(
            ImageManipulator::class,
            \Plank\Mediable\Facades\ImageManipulator::getFacadeRoot()
        );
    }

    public function test_it_sets_and_has_variants()
    {
        $manipulator = $this->getManipulator();
        $this->assertFalse($manipulator->hasVariantDefinition('foo'));
        $manipulator->defineVariant(
            'foo',
            new ImageManipulation($this->getMockCallable())
        );
        $this->assertTrue($manipulator->hasVariantDefinition('foo'));
    }

    public function test_it_throws_for_non_image_media()
    {
        $this->expectException(ImageManipulationException::class);
        $this->expectErrorMessage(
            "Cannot manipulate media with an aggregate type other than 'image', got 'document'."
        );
        $this->getManipulator()->createImageVariant(
            $this->makeMedia(['aggregate_type' => 'document']),
            'variant'
        );
    }

    public function test_it_throws_for_unknown_variants()
    {
        $this->expectException(ImageManipulationException::class);
        $this->expectErrorMessage("Unknown variant 'invalid'.");
        $this->getManipulator()->createImageVariant(
            $this->makeMedia(['aggregate_type' => 'image']),
            'invalid'
        );
    }

    public function test_it_throws_for_indeterminate_output_format()
    {
        $this->useFilesystem('tmp');
        $this->expectException(ImageManipulationException::class);
        $this->expectErrorMessage("Unable to determine valid output format for file.");
        $manipulation = ImageManipulation::make(
            function (Image $image) {
            }
        );
        $media = $this->makeMedia(
            [
                'disk' => 'tmp',
                'filename' => 'foo',
                'extension' => 'psd',
                'aggregate_type' => 'image'
            ]
        );
        $this->seedFileForMedia($media);
        $manipulator = $this->getManipulator();
        $manipulator->defineVariant('foo', $manipulation);
        $manipulator->createImageVariant($media, 'foo');
    }

    public function test_it_can_create_a_variant()
    {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = $this->makeMedia(
            [
                'id' => 10,
                'disk' => 'tmp',
                'directory' => 'foo',
                'filename' => 'bar',
                'extension' => 'png',
                'aggregate_type' => 'image'
            ]
        );
        $this->seedFileForMedia($media, $this->sampleFile());

        $beforeSave = $this->getMockCallable();
        $beforeSave->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->callback(
                    function (Media $media) {
                        $result = $media->directory === 'foo';
                        $media->directory = 'baz';
                        return $result;
                    }
                )
            );

        $manipulation = ImageManipulation::make(
            function (Image $image) {
                $image->resize(16, 16);
            }
        )->beforeSave($beforeSave);

        $imageManipulator = $this->getManipulator();
        $imageManipulator->defineVariant('test', $manipulation);
        $result = $imageManipulator->createImageVariant($media, 'test');

        $this->assertTrue($result->exists);
        $this->assertEquals('tmp', $result->disk);
        $this->assertEquals('baz', $result->directory);
        $this->assertEquals('bar-test', $result->filename);
        $this->assertEquals('png', $result->extension);
        $this->assertEquals('image/png', $result->mime_type);
        $this->assertEquals('image', $result->aggregate_type);
        $this->assertEquals(449, $result->size);
        $this->assertEquals('test', $result->variant_name);
        $this->assertEquals(10, $result->original_media_id);
        $this->assertTrue($media->fileExists());
    }

    public function test_it_can_create_a_variant_of_a_variant()
    {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = $this->makeMedia(
            [
                'id' => 20,
                'disk' => 'tmp',
                'directory' => 'foo',
                'filename' => 'bar',
                'extension' => 'png',
                'aggregate_type' => 'image',
                'variant_name' => 'other',
                'original_media_id' => 19
            ]
        );
        $this->seedFileForMedia($media, $this->sampleFile());

        $manipulation = ImageManipulation::make(
            function (Image $image) {
                $image->resize(16, 16);
            }
        );

        $imageManipulator = $this->getManipulator();
        $imageManipulator->defineVariant('test', $manipulation);
        $result = $imageManipulator->createImageVariant($media, 'test');

        $this->assertEquals('test', $result->variant_name);
        $this->assertEquals(19, $result->original_media_id);
        $this->assertTrue($media->fileExists());
    }

    public function formatProvider()
    {
        return [
            ['jpg', 'image/jpeg', 100],
            ['jpg', 'image/jpeg', 10],
            ['gif', 'image/gif', 90],
        ];
    }

    /**
     * @dataProvider formatProvider
     */
    public function test_it_can_create_a_variant_of_a_different_format(
        string $format,
        string $mime,
        int $quality
    ) {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = $this->makeMedia(
            [
                'disk' => 'tmp',
                'directory' => 'foo',
                'filename' => 'bar',
                'extension' => 'png',
                'aggregate_type' => 'image'
            ]
        );
        $this->seedFileForMedia($media, $this->sampleFile());

        $manipulation = ImageManipulation::make(
            function (Image $image) {
                $image->resize(16, 16);
            }
        )->setOutputFormat($format)->setOutputQuality($quality);

        $imageManipulator = $this->getManipulator();
        $imageManipulator->defineVariant('test', $manipulation);
        $result = $imageManipulator->createImageVariant($media, 'test');

        $this->assertEquals($format, $result->extension);
        $this->assertEquals($mime, $result->mime_type);
        $this->assertEquals('image', $result->aggregate_type);
        $this->assertTrue($media->fileExists());
    }

    public function getManipulator(): ImageManipulator
    {
        return new ImageManipulator(
            new ImageManager(['driver' => 'gd']),
            app(FilesystemManager::class)
        );
    }
}
