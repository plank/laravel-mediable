<?php

namespace Frasmage\Mediable;

use Frasmage\Mediable\Exceptions\MediaMoveException;
use Illuminate\Filesystem\FilesystemManager;

class MediaMover{
    protected $filesystem;

    public function __construct(FilesystemManager $filesystem){
        $this->filesystem = $filesystem;
    }

    public function move(Media $media, $directory, $filename = null){
        $storage = $this->filesystem->disk($media->disk);

        if ($filename) {
            $filename = $this->removeExtensionFromFilename($filename, $media->extension);
        } else {
            $filename = $media->filename;
        }

        $directory = trim($directory, '/');
        $target_path = $directory . '/' . $filename . '.' . $media->extension;

        if ($storage->has($target_path)) {
            throw MediaMoveException::destinationExists($target_path);
        }

        $storage->move($media->getDiskPath(), $target_path);
        $media->filename = $filename;
        $media->directory = $directory;
        $media->save();
    }

    /**
     * Remove the media's extension from a filename
     * @param  string $filename
     * @return string
     */
    protected function removeExtensionFromFilename($filename, $extension)
    {
        $extension = '.' . $extension;
        $extension_length = mb_strlen($filename) - mb_strlen($extension);
        if (mb_strrpos($filename, $extension) === $extension_length) {
            $filename = mb_substr($filename, 0, $extension_length);
        }
        return $filename;
    }
}
