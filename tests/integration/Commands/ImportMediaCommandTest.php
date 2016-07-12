<?php

use Plank\Mediable\Media;
use Plank\Mediable\Commands\ImportMediaCommand;
use Illuminate\Contracts\Console\Kernel as Artisan;

class ImportMediaCommandTest extends TestCase
{
    public function getEnvironmentSetUp($app){
        parent::getEnvironmentSetUp($app);
        $app['config']->set('mediable.allow_unrecognized_types', true);
        $app['config']->set('mediable.strict_type_checking', false);
    }

    public function test_it_creates_media_for_unmatched_files()
    {
        $artisan = $this->getArtisan();
        $media1 = factory(Media::class)->make(['disk' => 'tmp', 'filename' => 'foo']);
        $media2 = factory(Media::class)->create(['disk' => 'tmp', 'filename' => 'bar']);
        $this->seedFileForMedia($media1);
        $this->seedFileForMedia($media2);

        $artisan->call('media:import', ['disk' => 'tmp']);

        $this->assertEquals("Imported 1 file(s).\n", $artisan->output());
        $this->assertEquals(['bar', 'foo'], Media::orderBy('filename')->pluck('filename')->toArray());
    }

    public function test_it_creates_media_for_unmatched_files_in_directory()
    {
        $artisan = $this->getArtisan();
        $media1 = factory(Media::class)->make(['disk' => 'tmp', 'directory' => 'a', 'filename' => 'foo']);
        $media2 = factory(Media::class)->make(['disk' => 'tmp', 'directory' => 'a/b', 'filename' => 'bar']);
        $this->seedFileForMedia($media1);
        $this->seedFileForMedia($media2);

        $artisan->call('media:import', ['disk' => 'tmp', '--directory' => 'a/b']);

        $this->assertEquals("Imported 1 file(s).\n", $artisan->output());
        $this->assertEquals(['bar'], Media::pluck('filename')->toArray());
    }

    public function test_it_creates_media_for_unmatched_files_non_recursively()
    {
        $artisan = $this->getArtisan();
        $media1 = factory(Media::class)->make(['disk' => 'tmp', 'directory' => 'a', 'filename' => 'foo']);
        $media2 = factory(Media::class)->make(['disk' => 'tmp', 'directory' => 'a/b', 'filename' => 'bar']);
        $this->seedFileForMedia($media1);
        $this->seedFileForMedia($media2);

        $artisan->call('media:import', ['disk' => 'tmp', '--directory' => 'a', '--non-recursive' => true]);

        $this->assertEquals("Imported 1 file(s).\n", $artisan->output());
        $this->assertEquals(['foo'], Media::pluck('filename')->toArray());
    }

    public function test_it_skips_files_of_unmatched_type()
    {
        $artisan = $this->getArtisan();
        $filesystem = app(\Illuminate\Filesystem\FilesystemManager::class);
        $uploader = app('mediable.uploader');
        $uploader->setAllowUnrecognizedTypes(false);
        $command = new ImportMediaCommand($filesystem, $uploader);

        $media1 = factory(Media::class)->make(['disk' => 'tmp']);
        $this->seedFileForMedia($media1);

        $artisan->registerCommand($command);

        $artisan->call('media:import',['disk' => 'tmp']);
        $this->assertEquals("Imported 0 file(s).\nSkipped 1 unrecognized file(s).\n", $artisan->output());

    }

    public function test_it_updates_existing_media()
    {
        $artisan = $this->getArtisan();
        $media1 = factory(Media::class)->create([
            'disk' => 'tmp',
            'filename' => 'bar',
            'extension' => 'png',
            'type' => 'foo']);
        $media2 = factory(Media::class)->create([
            'disk' => 'tmp',
            'filename' => 'bar',
            'extension' => 'png',
            'size' => 8444,
            'mime_type' => 'image/png',
            'type' => 'image']);
        $this->seedFileForMedia($media1, fopen(__DIR__.'/../../_data/plank.png' ,'r'));
        $this->seedFileForMedia($media2, fopen(__DIR__.'/../../_data/plank.png' ,'r'));

        $artisan->call('media:import', ['disk' => 'tmp', '--force' => true]);
        $this->assertEquals(['image', 'image'], Media::pluck('type')->toArray());
        $this->assertEquals("Imported 0 file(s).\nUpdated 1 existing record(s).\nSkipped 1 unmodified record(s).\n", $artisan->output());

    }

    protected function getArtisan()
    {
        return app(Artisan::class);
    }
}
