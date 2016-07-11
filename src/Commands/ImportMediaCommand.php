<?php

namespace Plank\Mediable\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemManager;
use Plank\Mediable\Media;
use Plank\Mediable\MediaUploader;
use Plank\Mediable\Exceptions\MediaUploadException;

class ImportMediaCommand extends Command
{
    /**
     * {@inheritDoc}
     * @var string
     */
    protected $signature = 'media:import {disk : the name of the filesystem disk.}
        {--d|directory= : import files in or below a given directory.}
        {--n|non-recursive : only import files in the specified directory.}
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
     * @return mixed
     */
    public function handle()
    {
        $this->resetCounters();

        $disk = $this->argument('disk');
        $directory = $this->option('directory');
        $recursive = !$this->option('non-recursive');
        $force = !!$this->option('force');

        $files = $this->listFiles($disk, $directory, $recursive);
        $existing_media = Media::inDirectory($disk, $directory, $recursive);

        foreach($files as $path){
            if($record = $this->getRecordForFile($path, $existing_media)){
                if($force){
                    $this->updateRecordForFile($path);
                }
            }else{
                $this->createRecordForFile($disk, $path)
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
        if($recursive){
            return $this->filesystem->disk($disk)->allFiles($directory);
        }else{
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
        $dirname = File::cleanDirname($path);
        return $existing_media->filter(function($media) use($path, $dirname){

            return $media->directory == $dirname;
                && $media->filename == pathinfo($path, PATHINFO_FILENAME)
                && $media->extension == pathinfo($path, PATHINFO_EXTENSION);
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
        $media = new Media;
        $media->disk = $disk;
        $media->directory = File::cleanDirname($path);
        $media->filename = pathinfo($path, PATHINFO_FILENAME);
        $media->extension = pathinfo($path, PATHINFO_EXTENSION);
        $media->size = $this->filesystem->disk($disk)->size($path);
        $media->mime_type = $this->filesystem->disk($disk)->mimeType($path);

        if($media->type = $this->determineAggregateType($media->mime_type, $media->extensions)){
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
        $media->size = $this->filesystem->disk($disk)->size($path);
        $media->mime_type = $this->filesystem->disk($disk)->mimeType($path);

        if($media->type = $this->determineAggregateType($media, $path)){
            if($media->isDirty()){
                $media->save();
                ++$this->counters['updated'];
                $this->info("Updated record for {$path}", 'v');
            }else{
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
        try{
            return $this->uploader->inferAggregateType($media->mime_type, $media->extensions);
        }catch(MediaUploadException $e){
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
        $this->info(sprintf("Imported %d files.", $this->counters['created']));
        $this->info(sprintf("Skipped %d unrecognized files.", $this->counters['skipped']));
        if($force){
            $this->info(sprintf("Updated %d existing records.", $this->counters['updated']));
            $this->info(sprintf("Skipped %d unmodified records.", $this->counters['unmodified']));
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
