<?php

namespace Plank\Mediable;

use Plank\Mediable\Exceptions\MediaMoveException;
use Illuminate\Filesystem\FilesystemManager;

/**
 * Media Mover Class.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediaMover
{
    /**
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * Constructor.
     * @param \Illuminate\Filesystem\FilesystemManager $filesystem
     */
    public function __construct(FilesystemManager $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Move the file to a new location on disk.
     *
     * Will invoke the `save()` method on the model after the associated file has been moved to prevent synchronization errors
     * @param  \Plank\Mediable\Media $media
     * @param  string                $directory directory relative to disk root
     * @param  string                $filename  filename. Do not include extension
     * @return void
     * @throws \Plank\Mediable\Exceptions\MediaMoveException If attempting to change the file extension or a file with the same name already exists at the destination
     */
    public function move(Media $media, $directory, $filename = null)
    {
        $storage = $this->filesystem->disk($media->disk);

        if ($filename) {
            $filename = $this->removeExtensionFromFilename($filename, $media->extension);
        } else {
            $filename = $media->filename;
        }

        $directory = trim($directory, '/');
        $target_path = $directory.'/'.$filename.'.'.$media->extension;

        if ($storage->has($target_path)) {
            throw MediaMoveException::destinationExists($target_path);
        }

        $storage->move($media->getDiskPath(), $target_path);

        $media->filename = $filename;
        $media->directory = $directory;
        $media->save();
    }

    /**
     * Copy the file from one Media object to another one.
     *
     * Will invoke the `save()` method on the model after the associated file has been copied to prevent synchronization errors
     *
     * @param  \Plank\Mediable\Media $media
     * @param  \Plank\Mediable\Media $mediaFrom   the media the file is copied from
     * @param  string                $destination directory relative to disk root
     * @param  string                $filename    optional filename. Do not include extension
     * @return void
     * @throws \Plank\Mediable\Exceptions\MediaMoveException If attempting to change the file extension or a file with the same name already exists at the destination
     */
    public function copyFrom(Media $media, Media $mediaFrom, $destination, $filename = null)
    {
        $storageFrom = $this->filesystem->disk($mediaFrom->disk);
        $storageTo = $this->filesystem->disk($media->disk);

        if ($filename) {
            $filename = $this->removeExtensionFromFilename($filename, $media->extension);
        } else {
            $filename = $media->filename;
        }

        $directory = trim($destination, '/');
        $target_path = $directory.'/'.$filename.'.'.$media->extension;

        if ($storageTo->has($target_path)) {
            throw MediaMoveException::destinationExists($target_path);
        }

        $storageFrom->copy($mediaFrom->getDiskPath(), $target_path);

        // copy the media and set the new values
        $media->filename = $filename;
        $media->directory = $directory;
        $media->save();
    }

    /**
     * Remove the media's extension from a filename.
     * @param  string $filename
     * @param  string $extension
     * @return string
     */
    protected function removeExtensionFromFilename($filename, $extension)
    {
        $extension = '.'.$extension;
        $extension_length = mb_strlen($filename) - mb_strlen($extension);
        if (mb_strrpos($filename, $extension) === $extension_length) {
            $filename = mb_substr($filename, 0, $extension_length);
        }

        return $filename;
    }
}
