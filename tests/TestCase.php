<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Plank\Mediable\Media;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected $queriesCount;

    public function setUp()
    {
        parent::setUp();
        $this->withFactories(__DIR__.'/_factories');
    }

    protected function getPackageProviders($app)
    {
        return [
            Plank\Mediable\MediableServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'MediaUploader' => 'Plank\Mediable\MediaUploaderFacade',
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        if (file_exists(dirname(__DIR__) . '/.env')) {
            $dotenv = new Dotenv\Dotenv(dirname(__DIR__));
            $dotenv->load();
        }
        //use in-memory database
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);
        $app['config']->set('database.default', 'testing');

        $app['config']->set('filesystems.disks', [
            //private local storage
            'tmp' => [
                'driver' => 'local',
                'root' => storage_path('tmp'),
            ],
            //public local storage
            'uploads' => [
                'driver' => 'local',
                'root' => public_path('uploads'),
            ],
            'public_storage' => [
                'driver' => 'local',
                'root' => storage_path('public'),
                'visibility' => 'public',
                'prefix' => 'prefix'
            ],
            's3' => [
                'driver' => 's3',
                'key'    => env('S3_KEY'),
                'secret' => env('S3_SECRET'),
                'region' => env('S3_REGION'),
                'bucket' => env('S3_BUCKET'),
                'version' => 'latest',
                'visibility' => 'public'
            ]
        ]);

        $app['config']->set('mediable.allowed_disks', [
            'tmp',
            'uploads'
        ]);
    }

    protected function getPrivateProperty($class, $property_name)
    {
        $reflector = new ReflectionClass($class);
        $property = $reflector->getProperty($property_name);
        $property->setAccessible(true);
        return $property;
    }

    protected function getPrivateMethod($class, $method_name)
    {
        $reflector = new ReflectionClass($class);
        $method = $reflector->getMethod($method_name);
        $method->setAccessible(true);
        return $method;
    }

    protected function seedFileForMedia(Media $media, $contents = '')
    {
        app('filesystem')->disk($media->disk)->put($media->getDiskPath(), $contents);
    }

    protected function s3ConfigLoaded()
    {
        return env('S3_KEY') && env('S3_SECRET') && env('S3_REGION') && env('S3_BUCKET');
    }

    protected function useDatabase()
    {
        $artisan = $this->app->make(Kernel::class);
        $this->app->useDatabasePath(dirname(__DIR__));
        //Remigrate all database tables
        $artisan->call('migrate:refresh');
    }

    protected function useFilesystem($disk)
    {
        if (!$this->app['config']->has('filesystems.disks.' . $disk)) {
            return;
        }
        $root = $this->app['config']->get('filesystems.disks.' . $disk . '.root');
        $filesystem =  $this->app->make(Filesystem::class);
        $filesystem->cleanDirectory($root);
    }

    protected function useFilesystems()
    {
        $disks = $this->app['config']->get('filesystems.disks');
        foreach ($disks as $disk) {
            $this->useFilesystem($disk);
        }
    }
}
