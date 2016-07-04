<?php

namespace Frasmage\Mediable;

use Frasmage\Mediable\SourceAdapterFactory;
use Frasmage\Mediable\UploadSourceAdapters\RemoteUrl;
use Frasmage\Mediable\UploadSourceAdapters\LocalPath;
use Frasmage\Mediable\UploadSourceAdapters\FoundationFile;
use Frasmage\Mediable\UploadSourceAdapters\FoundationUploadedFile;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Mediable Service Provider
 *
 * Registers Laravel-Mediable package functionality
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediableServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/mediable.php' => config_path('mediable.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../migrations/2016_06_27_000000_create_mediable_tables.php' => database_path('migrations/2016_06_27_000000_create_mediable_tables.php')
        ], 'migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/mediable.php', 'mediable'
        );

        $this->registerSourceAdapterFactory();
        $this->registerUploader();
    }

    /**
     * Bind an instance of the Source Adapter Factory to the container
     *
     * Attaches the default adapter types
     * @return void
     */
    public function registerSourceAdapterFactory()
    {
        $this->app->singleton('mediable.factory', function (Container $app) {
            $factory = new SourceAdapterFactory;
            $adapters = $app['config']->get('mediable.source_adapters');

            foreach ($adapters['class'] as $source => $adapter) {
                $factory->setAdapterForClass($adapter, $source);
            }

            foreach ($adapters['pattern'] as $source => $adapter) {
                $factory->setAdapterForPattern($adapter, $source);
            }

            return $factory;
        });
        $this->app->alias('mediable.factory', SourceAdapterFactory::class);
    }

    /**
     * Bind the Media Uploader to the container
     * @return void
     */
    public function registerUploader()
    {
        $this->app->bind('mediable.uploader', function (Container $app) {
            return new MediaUploader($this->app['filesystem'], $this->app['mediable.factory'], $this->app['config']->get('mediable'));
        });
        $this->app->alias('mediable.uploader', MediaUploader::class);
    }
}
