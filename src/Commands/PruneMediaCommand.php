<?php

namespace Plank\Mediable\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemManager;
use Plank\Mediable\Media;

class PruneMediaCommand extends Command
{
    /**
     * {@inheritDoc}
     * @var string
     */
    protected $signature = 'media:prune {disk : the name of the filesystem disk.}
        {--d|directory= : prune records for files in or below a given directory.}
        {--n|non-recursive : only prune record for files in the specified directory.}';

    /**
     * {@inheritDoc}
     * @var string
     */
    protected $description = 'Delete media records that do not correspond to a file on disk';

    /**
     * Filesystem Manager instance
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * Constructor
     * @param FileSystemManager $filesystem
     */
    public function __construct(FileSystemManager $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    public function handle()
    {
        $disk = $this->argument('disk');
        $directory = $this->option('directory');
        $recursive = !$this->option('non-recursive');
        $counter = 0;

        $records = Media::inDirectory($disk, $directory, $recursive);

        foreach($records as $media){
            if(!$media->fileExists()){
                $media->delete();
                ++$counter;
                $this->info("Pruned record for file {$media->getDiskPath()}", 'v');
            }
        }

        $this->info("Pruned {$counter} records.");
    }
}
