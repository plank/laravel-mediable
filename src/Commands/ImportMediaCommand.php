<?php

namespace Plank\Mediable\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemManager;
use Plank\Mediable\Helpers\File;
use Plank\Mediable\Media;
use Plank\Mediable\MediaUploader;
use Plank\Mediable\Exceptions\MediaUploadException;

/**
 * Import Media Artisan Command
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class ImportMediaCommand extends Command
{
    /**
     * {@inheritDoc}
     * @var string
     */
    protected $signature = 'media:import {disk : the name of the filesystem disk.}
        {--d|directory= : import files in or below a given directory.}
        {--non-recursive : only import files in the specified directory.}
        {--f|force : re-process existing media.}';

    /**
     * {@inheritDoc}
     * @var string
     */
    protected $description = 'Create a media entity for each file on a disk';

    /**
     * Filesystem Manager instance
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * Uploader instance
     * @var MediaUploader
     */
    protected $uploader;

    /**
     * Various counters of files being modified
     * @var array
     */
    protected $counters = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'unmodified' => 0
    ];

    /**
     * Constructor
     * @param FileSystemManager $filesystem
     * @param MediaUploader     $uploader
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
        $recursive = !$this->option('non-recursive');
        $force = !!$this->option('force');

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
     * Generate a list of all files in the specified directory
     * @param  atring  $disk
     * @param  string  $directory
     * @param  boolean $recursive
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
     * Search through the record list for one matching the provided path
     * @param  string $path
     * @param  \Illuminate\Database\Eloquent\Collection $existing_media
     * @return Media|null
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
     * Generate a new media record
     * @param  string $disk
     * @param  string $path
     * @return void
     */
    protected function createRecordForFile($disk, $path)
    {
        $class = config('mediable.model');
        $media = new $class;
        $media->disk = $disk;
        $media->directory = File::cleanDirname($path);
        $media->filename = pathinfo($path, PATHINFO_FILENAME);
        $media->extension = pathinfo($path, PATHINFO_EXTENSION);
        $media->size = $this->filesystem->disk($disk)->size($path);
        $media->mime_type = $this->filesystem->disk($disk)->mimeType($path);

        if ($media->aggregate_type = $this->determineAggregateType($media, $path)) {
            $media->save();
            ++$this->counters['created'];
            $this->info("Created record for {$path}", 'v');
        }
    }

    /**
     * Update an existing media record
     * @param  Media $media
     * @param  string $path
     * @return void
     */
    protected function updateRecordForFile(Media $media, $path)
    {
        $media->size = $this->filesystem->disk($media->disk)->size($path);
        $media->mime_type = $this->filesystem->disk($media->disk)->mimeType($path);

        if ($media->aggregate_type = $this->determineAggregateType($media, $path)) {
            if ($media->isDirty()) {
                $media->save();
                ++$this->counters['updated'];
                $this->info("Updated record for {$path}", 'v');
            } else {
                ++$this->counters['unmodified'];
            }
        }
    }

    /**
     * Attempt to find a legal aggregate type for a Media record
     * @param  Media $media
     * @param  string $path
     * @return string|null
     */
    protected function determineAggregateType(Media $media, $path)
    {
        try {
            return $this->uploader->inferAggregateType($media->mime_type, $media->extensions);
        } catch (MediaUploadException $e) {
            ++$this->counters['skipped'];
            $this->warn($e->getMessage(), 'vvv');
            $this->info("Skipped unrecognized file at {$path}", 'v');
        }
        return null;
    }

    /**
     * Send the counter total to the console
     * @param  boolean $force
     * @return void
     */
    protected function outputCounters($force)
    {
        $this->info(sprintf("Imported %d file(s).", $this->counters['created']));
        if ($this->counters['skipped'] > 0) {
            $this->info(sprintf("Skipped %d unrecognized file(s).", $this->counters['skipped']));
        }
        if ($this->counters['updated'] > 0) {
            $this->info(sprintf("Updated %d existing record(s).", $this->counters['updated']));
        }
        if ($this->counters['unmodified'] > 0) {
            $this->info(sprintf("Skipped %d unmodified record(s).", $this->counters['unmodified']));
        }
    }

    /**
     * Reset the counters of processed files
     * @return void
     */
    protected function resetCounters()
    {
        $this->counters = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'unmodified' => 0
        ];
    }
}
