<?php

use Frasmage\Mediable\Media;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected $queriesCount;

    const DB_NAME     = 'mediable_test';
    const DB_USERNAME = 'testuser';
    const DB_PASSWORD = 'password';

    public function setUp()
    {
        parent::setUp();
        $this->withFactories(__DIR__.'/_factories');
        $this->resetDatabase();
        $this->emptyFilesystem('tmp');
        $this->emptyFilesystem('uploads');
    }

    protected function getPackageProviders($app)
    {
        return [
            Frasmage\Mediable\MediableServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'MediaUploader' => 'Frasmage\Mediable\MediaUploaderFacade',
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $dotenv = new Dotenv\Dotenv(dirname(__DIR__));
        $dotenv->load();
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
                'visibility' => 'public'
            ],
            's3' => [
                'driver' => 's3',
                'key'    => env('S3_KEY'),
                'secret' => env('S3_SECRET'),
                'region' => env('S3_REGION'),
                'bucket' => env('S3_BUCKET'),
                'version' => 'latest'
            ]
        ]);

        $app['config']->set('mediable.allowed_disks', [
            'tmp',
            'uploads'
        ]);

        //set up glide configs
        $app['config']->set('laravel-glide', [
            'source' => ['path' => public_path()],
            'cache' => ['path' => storage_path('glide/cache')],
            'baseURL' => 'glide',
            'maxSize' => 2000 * 2000,
            'useSecureURLs' => false //can't anticipate the hash
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

    private function resetDatabase()
    {
        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
        $database = $this->app['config']->get('database.default');

        //Remigrate all database tables
        $artisan->call('migrate:refresh', [
            '--database' => $database,
            '--realpath' => realpath(__DIR__.'/../migrations'),
        ]);
    }

    private function emptyFilesystem($disk)
    {
        if (!$this->app['config']->has('filesystems.disks.' . $disk)) {
            return;
        }
        $root = $this->app['config']->get('filesystems.disks.' . $disk . '.root');
        $filesystem =  $this->app->make(Illuminate\Filesystem\Filesystem::class);
        $filesystem->cleanDirectory($root);
    }
}
