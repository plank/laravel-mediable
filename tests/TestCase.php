<?php

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
        $this->withFactories(__DIR__.'/factories');
        $this->resetDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [Frasmage\Mediable\MediableServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'MediaUploader' => 'Frasmage\Mediable\MediaUploaderFacade'
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);
        $app['config']->set('filesystems.disks.tmp', [
            'driver' => 'local',
            'root' => storage_path('tmp'),
        ]);
        $app['config']->set('filesystems.disks.uploads', [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'visibility' => 'uploads'
        ]);
    }

    protected function getPrivateProperty($class, $property_name) {
        $reflector = new ReflectionClass($class);
        $property = $reflector->getProperty($property_name);
        $property->setAccessible(true);
        return $property;
    }

    protected function getPrivateMethod($class, $method_name){
        $reflector = new ReflectionClass($class);
        $method = $reflector->getMethod($method_name);
        $method->setAccessible(true);
        return $method;
    }

    private function resetDatabase()
    {
        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');

        // Makes sure the migrations table is created
        $artisan->call('migrate', [
            '--database' => 'testing',
            '--realpath' => realpath(__DIR__.'/../migrations'),
        ]);

        // We empty all tables
        $artisan->call('migrate:reset', [
            '--database' => 'testing',
        ]);

        // Migrate
        $artisan->call('migrate', [
            '--database' => 'testing',
            '--realpath'     => realpath(__DIR__.'/../migrations'),
        ]);
    }

    public function testSetup(){
        $this->assertTrue(true);
    }
}
