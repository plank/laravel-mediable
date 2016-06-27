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
            __DIR__.'/../migrations/' => database_path('migrations')
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
    public function registerSourceAdapterFactory(){
        $this->app->singleton('mediable.factory', function(Container $app){
           $factory = new SourceAdapterFactory;
           $factory->setAdapterForClass(FoundationUploadedFile::class, UploadedFile::class);
           $factory->setAdapterForClass(FoundationFile::class, File::class);
           $factory->setAdapterForPattern(RemoteUrl::class, '^https?://');
           $factory->setAdapterForPattern(LocalPath::class, '^/');
           return $factory;
        });
        $this->app->alias('mediable.factory', SourceAdapterFactory::class);
    }

    /**
     * Bind and instance of the Uploader to the container
     * @return void
     */
    public function registerUploader(){
        $this->app->singleton('mediable.uploader', function(Container $app){
            return new MediaUploader($this->app['filesystem'], $this->app['mediable.factory']);
        });
        $this->app->alias('mediable.uploader', MediaUploader::class);
    }

    /**
     * List the container bindings provided by the service provider.
     * @return array
     */
    public function provides(){
        return [
            'mediable.uploader',
            'mediable.factory'
        ];
    }

}
