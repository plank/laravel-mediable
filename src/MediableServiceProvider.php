<?php

declare(strict_types=1);

namespace Plank\Mediable;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\DriverInterface;
use Plank\Mediable\Commands\ImportMediaCommand;
use Plank\Mediable\Commands\PruneMediaCommand;
use Plank\Mediable\Commands\SyncMediaCommand;
use Plank\Mediable\SourceAdapters\SourceAdapterFactory;
use Plank\Mediable\UrlGenerators\UrlGeneratorFactory;

/**
 * Mediable Service Provider.
 *
 * Registers Laravel-Mediable package functionality
 */
class MediableServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        $root = dirname(__DIR__);
        $this->publishes(
            [
                $root . '/config/mediable.php' => config_path('mediable.php'),
            ],
            'config'
        );

        $time = time();

        if (empty(glob($this->app->databasePath('migrations/*_create_mediable_tables.php')))) {
            $this->publishes(
                [
                    $root . '/migrations/2016_06_27_000000_create_mediable_tables.php' =>
                    $this->app->databasePath(
                        'migrations/' . date(
                            'Y_m_d_His',
                            $time
                        ) . '_create_mediable_tables.php'
                    )
                ],
                'mediable-migrations'
            );
            $time++;
        }
        if (empty(glob($this->app->databasePath('migrations/*_add_variants_to_media.php')))) {
            $this->publishes(
                [
                    $root . '/migrations/2020_10_12_000000_add_variants_to_media.php' =>
                    $this->app->databasePath(
                        'migrations/' . date(
                            'Y_m_d_His',
                            $time
                        ) . '_add_variants_to_media.php'
                    )
                ],
                'mediable-migrations'
            );
            $time++;
        }

        if (empty(glob($this->app->databasePath('migrations/*_add_alt_to_media.php')))) {
            $this->publishes(
                [
                    $root . '/migrations/2024_03_30_000000_add_alt_to_media.php' =>
                    $this->app->databasePath(
                        'migrations/' . date(
                            'Y_m_d_His',
                            $time
                        ) . '_add_alt_to_media.php'
                    ),
                ],
                'mediable-migrations'
            );
        }

        if (!config('mediable.ignore_migrations', false)) {
            $this->loadMigrationsFrom($root . '/migrations');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/mediable.php',
            'mediable'
        );

        $this->registerSourceAdapterFactory();
        $this->registerImageManipulator();
        $this->registerUploader();
        $this->registerMover();
        $this->registerUrlGeneratorFactory();
        $this->registerConsoleCommands();
    }

    /**
     * Bind an instance of the Source Adapter Factory to the container.
     *
     * Attaches the default adapter types
     * @return void
     */
    public function registerSourceAdapterFactory(): void
    {
        $this->app->singleton('mediable.source.factory', function (Container $app) {
            $factory = new SourceAdapterFactory;

            $classAdapters = $app['config']->get('mediable.source_adapters.class', []);
            foreach ($classAdapters as $source => $adapter) {
                $factory->setAdapterForClass($adapter, $source);
            }

            $patternAdapters = $app['config']->get('mediable.source_adapters.pattern', []);
            foreach ($patternAdapters as $source => $adapter) {
                $factory->setAdapterForPattern($adapter, $source);
            }

            return $factory;
        });
        $this->app->alias('mediable.source.factory', SourceAdapterFactory::class);
    }

    /**
     * Bind the Media Uploader to the container.
     * @return void
     */
    public function registerUploader(): void
    {
        $this->app->bind('mediable.uploader', function (Container $app) {
            return new MediaUploader(
                $app['filesystem'],
                $app['mediable.source.factory'],
                $app[ImageManipulator::class],
                $app['config']->get('mediable')
            );
        });
        $this->app->alias('mediable.uploader', MediaUploader::class);
    }

    /**
     * Bind the Media Uploader to the container.
     * @return void
     */
    public function registerMover(): void
    {
        $this->app->bind('mediable.mover', function (Container $app) {
            return new MediaMover($app['filesystem']);
        });
        $this->app->alias('mediable.mover', MediaMover::class);
    }

    /**
     * Bind the Media Uploader to the container.
     * @return void
     */
    public function registerUrlGeneratorFactory(): void
    {
        $this->app->singleton('mediable.url.factory', function (Container $app) {
            $factory = new UrlGeneratorFactory;

            $config = $app['config']->get('mediable.url_generators', []);
            foreach ($config as $driver => $generator) {
                $factory->setGeneratorForFilesystemDriver($generator, $driver);
            }

            return $factory;
        });
        $this->app->alias('mediable.url.factory', UrlGeneratorFactory::class);
    }

    public function registerImageManipulator(): void
    {
        $this->app->singleton(ImageManipulator::class, function (Container $app) {
            return new ImageManipulator(
                $this->getInterventionImageManagerConfiguration($app),
                $app->get(FilesystemManager::class),
                $app->get(ImageOptimizer::class)
            );
        });
    }

    /**
     * Add package commands to artisan console.
     * @return void
     */
    public function registerConsoleCommands(): void
    {
        $this->commands([
            ImportMediaCommand::class,
            PruneMediaCommand::class,
            SyncMediaCommand::class,
        ]);
    }

    private function getInterventionImageManagerConfiguration(Container $app): ?ImageManager
    {
        $imageManager = null;
        if (
            $app->has(ImageManager::class)
            || (
                class_exists(DriverInterface::class) // intervention >= 3.0
                && $app->has(DriverInterface::class)
            )
        ) {
            // use whatever the user has bound to the container if available
            $imageManager = $app->get(ImageManager::class);
        } elseif (extension_loaded('imagick')) {
            // attempt to automatically configure for imagick
            if (class_exists(\Intervention\Image\Drivers\Imagick\Driver::class)) {
                // intervention/image >=3.0
                $imageManager = new ImageManager(
                    new \Intervention\Image\Drivers\Imagick\Driver()
                );
            } else {
                // intervention/image <3.0
                $imageManager = new ImageManager('imagick');
            }
        } elseif (extension_loaded('gd')) {
            // attempt to automatically configure for gd
            if (class_exists(\Intervention\Image\Drivers\Gd\Driver::class)) {
                // intervention/image >=3.0
                $imageManager = new ImageManager(
                    new \Intervention\Image\Drivers\Gd\Driver()
                );
            } else {
                // intervention/image <3.0
                $imageManager = new ImageManager('gd');
            }
        }

        return $imageManager;
    }
}
