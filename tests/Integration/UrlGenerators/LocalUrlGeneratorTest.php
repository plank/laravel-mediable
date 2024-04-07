<?php

namespace Plank\Mediable\Tests\Integration\UrlGenerators;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Storage;
use Plank\Mediable\Media;
use Plank\Mediable\Tests\TestCase;
use Plank\Mediable\UrlGenerators\LocalUrlGenerator;

class LocalUrlGeneratorTest extends TestCase
{
    public function test_it_generates_absolute_path(): void
    {
        $generator = $this->setupGenerator();
        $this->assertEquals(
            public_path('uploads/foo/bar.jpg'),
            $generator->getAbsolutePath()
        );
    }

    public function test_it_generates_url(): void
    {
        $generator = $this->setupGenerator();
        $this->assertEquals('http://localhost/uploads/foo/bar.jpg', $generator->getUrl());
    }

    public function test_it_attempts_to_generate_url_for_non_public_disk(): void
    {
        $generator = $this->setupGenerator('tmp');
        $this->assertEquals('/storage/foo/bar.jpg', $generator->getUrl());
    }

    public function test_it_accepts_public_visibility(): void
    {
        $generator = $this->setupGenerator('public_storage');
        $this->assertEquals('http://localhost/storage/foo/bar.jpg', $generator->getUrl());
    }

    public static function public_visibility_provider(): array
    {
        return [
            ['uploads', true, true],
            ['uploads', false, false],
            ['tmp', true, false],
            ['tmp', false, false],
            ['public_storage', true, true],
            ['public_storage', false, false],
        ];
    }

    /**
     * @dataProvider public_visibility_provider
     */
    public function test_it_checks_public_visibility(
        string $disk,
        bool $public,
        bool $expectedAccessibility
    ): void {
        $generator = $this->setupGenerator($disk, $public);
        $this->assertSame($expectedAccessibility, $generator->isPubliclyAccessible());
    }

    public function test_it_checks_public_visibility_mock_disk(): void
    {
        $filesystem = $this->createConfiguredMock(
            FilesystemManager::class,
            [
                'disk' => Storage::fake('uploads')
            ]
        );
        $generator = new LocalUrlGenerator(config(), $filesystem);

        $media = factory(Media::class)->make(
            [
                'disk' => 'uploads',
                'directory' => 'foo',
                'filename' => 'bar',
                'extension' => 'jpg'
            ]
        );
        $this->seedFileForMedia($media);
        $generator->setMedia($media);
        $this->assertTrue($generator->isPubliclyAccessible());
    }

    protected function setupGenerator(
        $disk = 'uploads',
        bool $public = null
    ): LocalUrlGenerator {
        /** @var Media $media */
        $media = factory(Media::class)->make(
            [
                'disk' => $disk,
                'directory' => 'foo',
                'filename' => 'bar',
                'extension' => 'jpg'
            ]
        );
        $this->useFilesystem($disk);
        $this->seedFileForMedia($media);

        if ($public === true) {
            $media->makePublic();
        }
        if ($public === false) {
            $media->makePrivate();
        }
        $generator = new LocalUrlGenerator(config(), app(FilesystemManager::class));
        $generator->setMedia($media);
        return $generator;
    }
}
