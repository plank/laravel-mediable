<?php
declare(strict_types=1);

namespace Plank\Mediable;

use Illuminate\Filesystem\FilesystemManager;
use Plank\Mediable\Exceptions\MediaMoveException;

/**
 * Media Mover Class.
 */
class MediaMover
{
    /**
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * Constructor.
     * @param FilesystemManager $filesystem
     */
    public function __construct(FilesystemManager $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Move the file to a new location on disk.
     *
     * Will invoke the `save()` method on the model after the associated file has been moved to prevent synchronization errors
     * @param  Media $media
     * @param  string $directory directory relative to disk root
     * @param  string $filename filename. Do not include extension
     * @return void
     * @throws MediaMoveException If attempting to change the file extension or a file with the same name already exists at the destination
     */
    public function move(Media $media, string $directory, string $filename = null): void
    {
        $storage = $this->filesystem->disk($media->disk);

        if ($filename) {
            $filename = $this->removeExtensionFromFilename($filename, $media->extension);
        } else {
            $filename = $media->filename;
        }

        $directory = trim($directory, '/');
        $targetPath = $directory . '/' . $filename . '.' . $media->extension;

        if ($storage->has($targetPath)) {
            throw MediaMoveException::destinationExists($targetPath);
        }

        $storage->move($media->getDiskPath(), $targetPath);

        $media->filename = $filename;
        $media->directory = $directory;
        $media->save();
    }

    /**
     * Copy the file from one Media object to another one.
     *
     * This method creates a new Media object as well as duplicates the associated file on the disk.
     *
     * @param  Media $media The media to copy from
     * @param  string $directory directory relative to disk root
     * @param  string $filename optional filename. Do not include extension
     *
     * @return Media
     * @throws MediaMoveException If a file with the same name already exists at the destination or it fails to copy the file
     */
    public function copyTo(Media $media, string $directory, string $filename = null): Media
    {
        $storage = $this->filesystem->disk($media->disk);

        if ($filename) {
            $filename = $this->removeExtensionFromFilename($filename, $media->extension);
        } else {
            $filename = $media->filename;
        }

        $directory = trim($directory, '/');
        $targetPath = $directory . '/' . $filename . '.' . $media->extension;

        if ($storage->has($targetPath)) {
            throw MediaMoveException::destinationExists($targetPath);
        }

        try {
            $storage->copy($media->getDiskPath(), $targetPath);
        } catch (\Exception $exception) {
            throw MediaMoveException::failedToCopy($media->getDiskPath(), $targetPath);
        }

        // now we copy the Media object
        /** @var Media $newMedia */
        $newMedia = $media->replicate();
        $newMedia->filename = $filename;
        $newMedia->directory = $directory;

        $newMedia->save();

        return $newMedia;
    }

    /**
     * Remove the media's extension from a filename.
     * @param  string $filename
     * @param  string $extension
     * @return string
     */
    protected function removeExtensionFromFilename(string $filename, string $extension): string
    {
        $extension = '.' . $extension;
        $extensionLength = mb_strlen($filename) - mb_strlen($extension);
        if (mb_strrpos($filename, $extension) === $extensionLength) {
            $filename = mb_substr($filename, 0, $extensionLength);
        }

        return $filename;
    }
}
