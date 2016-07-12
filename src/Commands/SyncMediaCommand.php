<?php

namespace Plank\Mediable\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemManager;
use Plank\Mediable\Media;

/**
 * Synchronize Media Artisan Command
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class SyncMediaCommand extends Command
{
    /**
     * {@inheritDoc}
     * @var string
     */
    protected $signature = 'media:sync {disk : the name of the filesystem disk.}
        {--d|directory= : prune records for files in or below a given directory.}
        {--n|non-recursive : only prune record for files in the specified directory.}
        {--f|force : re-process existing media.}';

    /**
     * {@inheritDoc}
     * @var string
     */
    protected $description = 'Synchronize media records with the filesystem.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $disk = $this->argument('disk');
        $directory = $this->argument('directory') ?: '';
        $non_recursive = !!$this->argument('non-recursive');
        $force = !!$this->argument('force');

        $this->call('media:prune', [
            'disk' => $disk,
            '--directory' => $directory,
            '--non-recursive' => $non_recursive
        ]);

        $this->call('media:sync', [
            'disk' => $disk,
            '--directory' => $directory,
            '--non-recursive' => $non_recursive,
            '--force' => $force
        ]);
    }
}
