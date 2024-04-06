<?php

namespace Plank\Mediable\Tests;

use Dotenv\Dotenv;
use Faker\Factory;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Filesystem\Filesystem;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Plank\Mediable\Media;
use Plank\Mediable\MediableServiceProvider;
use Plank\Mediable\Tests\Mocks\MockCallable;
use ReflectionClass;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;

class TestCase extends BaseTestCase
{
    const TEST_FILE_SIZE = 7173;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFactories(__DIR__ . '/Factories');
    }

    protected function getPackageProviders($app)
    {
        return [
            MediableServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'MediaUploader' => \Plank\Mediable\Facades\MediaUploader::class,
            'ImageManipulator' => \Plank\Mediable\Facades\ImageManipulator::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        if (file_exists(dirname(__DIR__) . '/.env')) {
            Dotenv::createImmutable(dirname(__DIR__))->load();
        }
        //use in-memory database
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('filesystems.default', 'uploads');
        $app['config']->set('filesystems.disks', [
            //private local storage
            'tmp' => [
                'driver' => 'local',
                'root' => storage_path('tmp'),
                'visibility' => 'private'
            ],
            'novisibility' => [
                'driver' => 'local',
                'root' => storage_path('tmp'),
            ],
            //public local storage
            'uploads' => [
                'driver' => 'local',
                'root' => public_path('uploads'),
                'url' => 'http://localhost/uploads',
                'visibility' => 'public'
            ],
            'public_storage' => [
                'driver' => 'local',
                'root' => storage_path('public'),
                'url' => 'http://localhost/storage',
                'visibility' => 'public',
            ],
            's3' => [
                'driver' => 's3',
                'key' => env('S3_KEY'),
                'secret' => env('S3_SECRET'),
                'region' => env('S3_REGION'),
                'bucket' => env('S3_BUCKET'),
                'version' => 'latest',
                'visibility' => 'public',
                // set random root to avoid parallel test runs from deleting each other's files
                'root' => Factory::create()->md5
            ],
            'scoped' => [
                'driver' => 'scoped',
                'disk' => 'tmp',
                'prefix' => 'scoped'
            ],
        ]);

        $app['config']->set('mediable.allowed_disks', [
            'tmp',
            'novisibility',
            'uploads'
        ]);

        $app['config']->set('mediable.image_optimization.enabled', false);

        if (class_exists(Driver::class)) {
            $app->instance(ImageManager::class, new ImageManager(new Driver()));
        }
    }

    protected function getPrivateProperty($class, $property_name): \ReflectionProperty
    {
        $reflector = new ReflectionClass($class);
        $property = $reflector->getProperty($property_name);
        $property->setAccessible(true);
        return $property;
    }

    protected function getPrivateMethod($class, $method_name): \ReflectionMethod
    {
        $reflector = new ReflectionClass($class);
        $method = $reflector->getMethod($method_name);
        $method->setAccessible(true);
        return $method;
    }

    protected function seedFileForMedia(Media $media, $contents = ''): void
    {
        app('filesystem')->disk($media->disk)->put(
            $media->getDiskPath(),
            $contents,
            config("filesystems.disks.{$media->disk}.visibility")
        );
    }

    protected function s3ConfigLoaded(): bool
    {
        return env('S3_KEY') && env('S3_SECRET') && env('S3_REGION') && env('S3_BUCKET');
    }

    protected function useDatabase(): void
    {
        $this->app->useDatabasePath(dirname(__DIR__));
        $this->loadMigrationsFrom(
            [
                '--path' => [
                    dirname(__DIR__) . '/migrations',
                    __DIR__ . '/migrations'
                ]
            ]
        );
    }

    protected function useFilesystem($disk): void
    {
        if (!$this->app['config']->has('filesystems.disks.' . $disk)) {
            return;
        }
        $root = $this->app['config']->get('filesystems.disks.' . $disk . '.root');
        $filesystem = $this->app->make(Filesystem::class);
        $filesystem->cleanDirectory($root);
    }

    protected function useFilesystems(): void
    {
        $disks = $this->app['config']->get('filesystems.disks');
        foreach ($disks as $disk) {
            $this->useFilesystem($disk);
        }
    }

    protected static function sampleFilePath(): string
    {
        return realpath(__DIR__ . '/_data/plank.png');
    }

    protected static function alternateFilePath(): string
    {
        return realpath(__DIR__ . '/_data/plank2.png');
    }

    protected static function remoteFilePath(): string
    {
        return 'https://raw.githubusercontent.com/plank/laravel-mediable/master/tests/_data/plank.png';
    }

    protected function sampleFile()
    {
        return Utils::tryFopen(TestCase::sampleFilePath(), 'r');
    }

    protected function makeMedia(array $attributes = []): Media
    {
        return factory(Media::class)->make($attributes);
    }

    protected function createMedia(array $attributes = []): Media
    {
        return factory(Media::class)->create($attributes);
    }

    /**
     * @return callable&MockObject
     */
    protected function getMockCallable(): callable
    {
        return $this->createPartialMock(MockCallable::class, ['__invoke']);
    }

    /**
     * @param array<mixed> $firstCallArguments
     * @param array<mixed> ...$consecutiveCallsArguments
     *
     * @return \Generator<int,Callback<mixed>>
     */
    public static function withConsecutive(array $firstCallArguments, array ...$consecutiveCallsArguments): \Generator
    {
        foreach ($consecutiveCallsArguments as $consecutiveCallArguments) {
            self::assertSameSize($firstCallArguments, $consecutiveCallArguments, 'Each expected arguments list need to have the same size.');
        }

        $allConsecutiveCallsArguments = [$firstCallArguments, ...$consecutiveCallsArguments];

        $numberOfArguments = count($firstCallArguments);
        $argumentList      = [];
        for ($argumentPosition = 0; $argumentPosition < $numberOfArguments; $argumentPosition++) {
            $argumentList[$argumentPosition] = array_column($allConsecutiveCallsArguments, $argumentPosition);
        }

        $mockedMethodCall = 0;
        $callbackCall     = 0;
        foreach ($argumentList as $index => $argument) {
            yield new Callback(
                static function (mixed $actualArgument) use ($argumentList, &$mockedMethodCall, &$callbackCall, $index, $numberOfArguments): bool {
                    $expected = $argumentList[$index][$mockedMethodCall] ?? null;

                    $callbackCall++;
                    $mockedMethodCall = (int) ($callbackCall / $numberOfArguments);

                    if ($expected instanceof Constraint) {
                        self::assertThat($actualArgument, $expected);
                    } else {
                        self::assertEquals($expected, $actualArgument);
                    }

                    return true;
                },
            );
        }
    }
}
