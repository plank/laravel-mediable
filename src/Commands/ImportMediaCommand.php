<?php

namespace Plank\Mediable\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemManager;
use Plank\Mediable\Helpers\File;
use Plank\Mediable\Media;
use Plank\Mediable\MediaUploader;
use Plank\Mediable\Exceptions\MediaUploadException;

/**
 * Import Media Artisan Command.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class ImportMediaCommand extends Command
{
    /**
     * {@inheritdoc}
     * @var string
     */
    protected $signature = 'media:import {disk : the name of the filesystem disk.}
        {--d|directory= : import files in or below a given directory.}
        {--non-recursive : only import files in the specified directory.}
        {--f|force : re-process existing media.}';

    /**
     * {@inheritdoc}
     * @var string
     */
    protected $description = 'Create a media entity for each file on a disk';

    /**
     * Filesystem Manager instance.
     * @var \Illuminate\Filesystem\FilesystemManager
     */
    protected $filesystem;

    /**
     * Uploader instance.
     * @var \Plank\Mediable\MediaUploader
     */
    protected $uploader;

    /**
     * Various counters of files being modified.
     * @var array
     */
    protected $counters = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
    ];

    /**
     * Constructor.
     * @param \Illuminate\Filesystem\FilesystemManager $filesystem
     * @param \Plank\Mediable\MediaUploader            $uploader
     */
    public function __construct(FileSystemManager $filesystem, MediaUploader $uploader)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
        $this->uploader = $uploader;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->resetCounters();

        $disk = $this->argument('disk');
        $directory = $this->option('directory') ?: '';
        $recursive = ! $this->option('non-recursive');
        $force = (bool) $this->option('force');

        $files = $this->listFiles($disk, $directory, $recursive);
        $existing_media = Media::inDirectory($disk, $directory, $recursive)->get();

        foreach ($files as $path) {
            if ($record = $this->getRecordForFile($path, $existing_media)) {
                if ($force) {
                    $this->updateRecordForFile($record, $path);
                }
            } else {
                $this->createRecordForFile($disk, $path);
            }
        }

        $this->outputCounters($force);
    }

    /**
     * Generate a list of all files in the specified directory.
     * @param  atring  $disk
     * @param  string  $directory
     * @param  bool $recursive
     * @return array
     */
    protected function listFiles($disk, $directory = '', $recursive = true)
    {
        if ($recursive) {
            return $this->filesystem->disk($disk)->allFiles($directory);
        } else {
            return $this->filesystem->disk($disk)->files($directory);
        }
    }

    /**
     * Search through the record list for one matching the provided path.
     * @param  string $path
     * @param  \Illuminate\Database\Eloquent\Collection $existing_media
     * @return \Plank\Mediable\Media|null
     */
    protected function getRecordForFile($path, $existing_media)
    {
        $directory = File::cleanDirname($path);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $existing_media->filter(function (Media $media) use ($directory, $filename, $extension) {
            return $media->directory == $directory && $media->filename == $filename && $media->extension == $extension;
        })->first();
    }

    /**
     * Generate a new media record.
     * @param  string $disk
     * @param  string $path
     * @return void
     */
    protected function createRecordForFile($disk, $path)
    {
        try {
            $this->uploader->importPath($disk, $path);
            ++$this->counters['created'];
            $this->info("Created Record for file at {$path}", 'v');
        } catch (MediaUploadException $e) {
            $this->warn($e->getMessage(), 'vvv');
            ++$this->counters['skipped'];
            $this->info("Skipped file at {$path}", 'v');
        }
    }

    /**
     * Update an existing media record.
     * @param  \Plank\Mediable\Media $media
     * @param  string $path
     * @return void
     */
    protected function updateRecordForFile(Media $media, $path)
    {
        try {
            if ($this->uploader->update($media)) {
                ++$this->counters['updated'];
                $this->info("Updated record for {$path}", 'v');
            } else {
                ++$this->counters['skipped'];
                $this->info("Skipped unmodified file at {$path}", 'v');
            }
        } catch (MediaUploadException $e) {
            $this->warn($e->getMessage(), 'vvv');
            ++$this->counters['skipped'];
            $this->info("Skipped file at {$path}", 'v');
        }
    }

    /**
     * Send the counter total to the console.
     * @param  bool $force
     * @return void
     */
    protected function outputCounters($force)
    {
        $this->info(sprintf('Imported %d file(s).', $this->counters['created']));
        if ($this->counters['updated'] > 0) {
            $this->info(sprintf('Updated %d record(s).', $this->counters['updated']));
        }
        if ($this->counters['skipped'] > 0) {
            $this->info(sprintf('Skipped %d file(s).', $this->counters['skipped']));
        }
    }

    /**
     * Reset the counters of processed files.
     * @return void
     */
    protected function resetCounters()
    {
        $this->counters = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];
    }
}
