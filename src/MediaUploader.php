<?php

namespace Plank\Mediable;

use Plank\Mediable\Exceptions\MediaUpload\FileSizeException;
use Plank\Mediable\Exceptions\MediaUpload\FileExistsException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotFoundException;
use Plank\Mediable\Exceptions\MediaUpload\ForbiddenException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\Helpers\File;
use Plank\Mediable\SourceAdapters\RawContentAdapter;
use Plank\Mediable\SourceAdapters\SourceAdapterFactory;
use Illuminate\Filesystem\FilesystemManager;

/**
 * Media Uploader.
 *
 * Validates files, uploads them to disk and generates Media
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediaUploader
{
    const ON_DUPLICATE_REPLACE = 'replace';
    const ON_DUPLICATE_INCREMENT = 'increment';
    const ON_DUPLICATE_ERROR = 'error';

    /**
     * @var FileSystemManager
     */
    private $filesystem;

    /**
     * @var SourceAdapterFactory
     */
    private $factory;

    /**
     * Mediable configurations.
     * @var array
     */
    private $config;

    /**
     * Source adapter.
     * @var
     */
    private $source;

    /**
     * Name of the filesystem disk.
     * @var string
     */
    private $disk;

    /**
     * Path relative to the filesystem disk root.
     * @var string
     */
    private $directory = '';

    /**
     * Name of the new file.
     * @var string
     */
    private $filename = null;

    /**
     * If true the contents hash of the source will be used as the filename.
     * @var bool
     */
    private $hash_filename = false;

    /**
     * Constructor.
     * @param \Illuminate\Filesystem\FilesystemManager            $filesystem
     * @param \Plank\Mediable\SourceAdapters\SourceAdapterFactory $factory
     * @param array|null                                          $config
     */
    public function __construct(FileSystemManager $filesystem, SourceAdapterFactory $factory, $config = null)
    {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
        $this->config = $config ?: config('mediable');
    }

    /**
     * Set the source for the file.
     * @param  mixed $source
     * @return static
     */
    public function fromSource($source)
    {
        $this->source = $this->factory->create($source);

        return $this;
    }

    /**
     * Set the source for the string data.
     * @param  string $source
     * @return static
     */
    public function fromString($source)
    {
        $this->source = new RawContentAdapter($source);

        return $this;
    }

    /**
     * Set the filesystem disk and relative directory where the file will be saved.
     * @param  string $disk
     * @param  string $directory
     * @return static
     */
    public function toDestination($disk, $directory)
    {
        return $this->toDisk($disk)->toDirectory($directory);
    }

    /**
     * Set the filesystem disk on which the file will be saved.
     * @param string $disk
     * @return static
     */
    public function toDisk($disk)
    {
        $this->disk = $this->verifyDisk($disk);

        return $this;
    }

    /**
     * Set the directory relative to the filesystem disk at which the file will be saved.
     * @param string $directory
     * @return static
     */
    public function toDirectory($directory)
    {
        $this->directory = trim($this->sanitizePath($directory), DIRECTORY_SEPARATOR);

        return $this;
    }

    /**
     * Specify the filename to copy to the file to.
     * @param string $filename
     * @return static
     */
    public function useFilename($filename)
    {
        $this->filename = $this->sanitizeFilename($filename);
        $this->hash_filename = false;

        return $this;
    }

    /**
     * Indicates to the uploader to generate a filename using the file's MD5 hash.
     * @return static
     */
    public function useHashForFilename()
    {
        $this->hash_filename = true;
        $this->filename = null;

        return $this;
    }

    /**
     * Restore the default behaviour of using the source file's filename.
     * @return static
     */
    public function useOriginalFilename()
    {
        $this->filename = null;
        $this->hash_filename = false;

        return $this;
    }

    /**
     * Change the class to use for generated Media.
     * @param string $class
     * @return static
     * @throws \Plank\Mediable\Exceptions\MediaUpload\ConfigurationException if $class does not extend Plank\Mediable\Media
     */
    public function setModelClass($class)
    {
        if (! is_subclass_of($class, Media::class)) {
            throw ConfigurationException::cannotSetModel($class);
        }
        $this->config['model'] = $class;

        return $this;
    }

    /**
     * Change the maximum allowed filesize.
     * @param int $size
     * @return static
     */
    public function setMaximumSize($size)
    {
        $this->config['max_size'] = (int) $size;

        return $this;
    }

    /**
     * Change the behaviour for when a file already exists at the destination.
     * @param string $behavior
     * @return static
     */
    public function setOnDuplicateBehavior($behavior)
    {
        $this->config['on_duplicate'] = (string) $behavior;

        return $this;
    }

    /**
     * Get current behavior when duplicate file is uploaded.
     *
     * @return string
     */
    public function getOnDuplicateBehavior()
    {
        return $this->config['on_duplicate'];
    }

    /**
     * Throw an exception when file already exists at the destination.
     *
     * @return static
     */
    public function onDuplicateError()
    {
        return $this->setOnDuplicateBehavior(self::ON_DUPLICATE_ERROR);
    }

    /**
     * Append incremented counter to file name when file already exists at destination.
     *
     * @return static
     */
    public function onDuplicateIncrement()
    {
        return $this->setOnDuplicateBehavior(self::ON_DUPLICATE_INCREMENT);
    }

    /**
     * Overwrite existing file when file already exists at destination.
     *
     * @return static
     */
    public function onDuplicateReplace()
    {
        return $this->setOnDuplicateBehavior(self::ON_DUPLICATE_REPLACE);
    }

    /**
     * Change whether both the MIME type and extensions must match the same aggregate type.
     * @param bool $strict
     * @return static
     */
    public function setStrictTypeChecking($strict)
    {
        $this->config['strict_type_checking'] = (bool) $strict;

        return $this;
    }

    /**
     * Change whether files not matching any aggregate types are allowed.
     * @param bool $allow
     * @return static
     */
    public function setAllowUnrecognizedTypes($allow)
    {
        $this->config['allow_unrecognized_types'] = (bool) $allow;

        return $this;
    }

    /**
     * Add or update the definition of a aggregate type.
     * @param string $type       the name of the type
     * @param array  $mime_types list of MIME types recognized
     * @param array  $extensions list of file extensions recognized
     * @return static
     */
    public function setTypeDefinition($type, $mime_types, $extensions)
    {
        $this->config['aggregate_types'][$type] = [
            'mime_types' => (array) $mime_types,
            'extensions' => (array) $extensions,
        ];

        return $this;
    }

    /**
     * Set a list of MIME types that the source file must be restricted to.
     * @param array $allowed_mimes
     * @return static
     */
    public function setAllowedMimeTypes($allowed_mimes)
    {
        $this->config['allowed_mime_types'] = array_map('strtolower', (array)$allowed_mimes);

        return $this;
    }

    /**
     * Set a list of file extensions that the source file must be restricted to.
     * @param array $allowed_extensions
     * @return static
     */
    public function setAllowedExtensions($allowed_extensions)
    {
        $this->config['allowed_extensions'] = array_map('strtolower', (array)$allowed_extensions);

        return $this;
    }

    /**
     * Set a list of aggregate types that the source file must be restricted to.
     * @param array $allowed_types
     * @return static
     */
    public function setAllowedAggregateTypes($allowed_types)
    {
        $this->config['allowed_aggregate_types'] = $allowed_types;

        return $this;
    }

    /**
     * Determine the aggregate type of the file based on the MIME type and the extension.
     * @param  string $mime_type
     * @param  string $extension
     * @return string
     * @throws \Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException If the file type is not recognized
     * @throws \Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException If the file type is restricted
     * @throws \Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException If the aggregate type is restricted
     */
    public function inferAggregateType($mime_type, $extension)
    {
        $allowed_types = $this->config['allowed_aggregate_types'];
        $types_for_mime = $this->possibleAggregateTypesForMimeType($mime_type);
        $types_for_extension = $this->possibleAggregateTypesForExtension($extension);

        if (count($allowed_types)) {
            $intersection = array_intersect($types_for_mime, $types_for_extension, $allowed_types);
        } else {
            $intersection = array_intersect($types_for_mime, $types_for_extension);
        }

        if (count($intersection)) {
            $type = $intersection[0];
        } elseif (empty($types_for_mime) && empty($types_for_extension)) {
            if (! $this->config['allow_unrecognized_types']) {
                throw FileNotSupportedException::unrecognizedFileType($mime_type, $extension);
            }
            $type = Media::TYPE_OTHER;
        } else {
            if ($this->config['strict_type_checking']) {
                throw FileNotSupportedException::strictTypeMismatch($mime_type, $extension);
            }
            $merged = array_merge($types_for_mime, $types_for_extension);
            $type = reset($merged);
        }

        if (count($allowed_types) && ! in_array($type, $allowed_types)) {
            throw FileNotSupportedException::aggregateTypeRestricted($type, $allowed_types);
        }

        return $type;
    }

    /**
     * Determine the aggregate type of the file based on the MIME type.
     * @param  string $mime
     * @return string
     */
    public function possibleAggregateTypesForMimeType($mime)
    {
        $types = [];
        foreach ($this->config['aggregate_types'] as $type => $attributes) {
            if (in_array($mime, $attributes['mime_types'])) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * Determine the aggregate type of the file based on the extension.
     * @param  string $extension
     * @return string|null
     */
    public function possibleAggregateTypesForExtension($extension)
    {
        $types = [];
        foreach ($this->config['aggregate_types'] as $type => $attributes) {
            if (in_array($extension, $attributes['extensions'])) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * Process the file upload.
     *
     * Validates the source, then stores the file onto the disk and creates and stores a new Media instance.
     * @return \Plank\Mediable\Media
     */
    public function upload()
    {
        $this->verifySource();

        $model = $this->makeModel();

        $model->size = $this->verifyFileSize($this->source->size());
        $model->mime_type = $this->verifyMimeType($this->source->mimeType());
        $model->extension = $this->verifyExtension($this->source->extension());
        $model->aggregate_type = $this->inferAggregateType($model->mime_type, $model->extension);

        $model->disk = $this->disk ?: $this->config['default_disk'];
        $model->directory = $this->directory;
        $model->filename = $this->generateFilename();

        $this->verifyDestination($model);

        $this->filesystem->disk($model->disk)->put($model->getDiskPath(), $this->source->contents());
        $model->save();

        return $model;
    }

    /**
     * Create a `Media` record for a file already on a disk.
     * @param  string $disk
     * @param  string $path Path to file, relative to disk root
     * @return \Plank\Mediable\Media
     */
    public function importPath($disk, $path)
    {
        $directory = File::cleanDirname($path);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $this->import($disk, $directory, $filename, $extension);
    }

    /**
     * Create a `Media` record for a file already on a disk.
     * @param  string $disk
     * @param  string $directory
     * @param  string $filename
     * @param  string $extension
     * @return \Plank\Mediable\Media
     * @throws \Plank\Mediable\Exceptions\MediaUploadFileNotFoundException If the file does not exist
     */
    public function import($disk, $directory, $filename, $extension)
    {
        $disk = $this->verifyDisk($disk);
        $storage = $this->filesystem->disk($disk);

        $model = $this->makeModel();
        $model->disk = $disk;
        $model->directory = $directory;
        $model->filename = $filename;
        $model->extension = $this->verifyExtension($extension);

        if (! $storage->has($model->getDiskPath())) {
            throw FileNotFoundException::fileNotFound($model->getDiskPath());
        }

        $model->mime_type = $this->verifyMimeType($storage->mimeType($model->getDiskPath()));
        $model->aggregate_type = $this->inferAggregateType($model->mime_type, $model->extension);
        $model->size = $this->verifyFileSize($storage->size($model->getDiskPath()));

        $model->save();

        return $model;
    }

    /**
     * Reanalyze a media record's file and adjust the aggregate type and size, if necessary.
     * @param  \Plank\Mediable\Media  $media
     * @return bool Whether the model was modified
     */
    public function update(Media $media)
    {
        $storage = $this->filesystem->disk($media->disk);

        $media->size = $this->verifyFileSize($storage->size($media->getDiskPath()));
        $media->mime_type = $this->verifyMimeType($storage->mimeType($media->getDiskPath()));
        $media->aggregate_type = $this->inferAggregateType($media->mime_type, $media->extension);

        if ($dirty = $media->isDirty()) {
            $media->save();
        }

        return $dirty;
    }

    /**
     * Generate an instance of the `Media` class.
     * @return \Plank\Mediable\Media
     */
    private function makeModel()
    {
        $class = $this->config['model'];

        return new $class;
    }

    /**
     * Ensure that the provided filesystem disk name exists and is allowed.
     * @param  string $disk
     * @return string
     * @throws \Plank\Mediable\Exceptions\MediaUpload\ConfigurationException If the disk does not exist
     * @throws \Plank\Mediable\Exceptions\MediaUpload\ForbiddenException If the disk is not included in the `allowed_disks` config.
     */
    private function verifyDisk($disk)
    {
        if (! array_key_exists($disk, config('filesystems.disks'))) {
            throw ConfigurationException::diskNotFound($disk);
        }

        if (! in_array($disk, $this->config['allowed_disks'])) {
            throw ForbiddenException::diskNotAllowed($disk);
        }

        return $disk;
    }

    /**
     * Ensure that a valid source has been provided.
     * @return void
     * @throws \Plank\Mediable\Exceptions\MediaUpload\ConfigurationException If no source is provided
     * @throws \Plank\Mediable\Exceptions\MediaUpload\FileNotFoundException If the source is invalid
     */
    private function verifySource()
    {
        if (empty($this->source)) {
            throw ConfigurationException::noSourceProvided();
        }
        if (! $this->source->valid()) {
            throw FileNotFoundException::fileNotFound($this->source->path());
        }
    }

    /**
     * Ensure that the file's mime type is allowed.
     * @param  string $mime_type
     * @return string
     * @throws \Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException If the mime type is not allowed
     */
    private function verifyMimeType($mime_type)
    {
        $allowed = $this->config['allowed_mime_types'];
        if (! empty($allowed) && ! in_array(strtolower($mime_type), $allowed)) {
            throw FileNotSupportedException::mimeRestricted(strtolower($mime_type), $allowed);
        }

        return $mime_type;
    }

    /**
     * Ensure that the file's extension is allowed.
     * @param  string $extension
     * @return string
     * @throws \Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException If the file extension is not allowed
     */
    private function verifyExtension($extension)
    {
        $allowed = $this->config['allowed_extensions'];
        if (! empty($allowed) && ! in_array(strtolower($extension), $allowed)) {
            throw FileNotSupportedException::extensionRestricted(strtolower($extension), $allowed);
        }

        return $extension;
    }

    /**
     * Verify that the file being uploaded is not larger than the maximum.
     * @param  int $size
     * @return int
     * @throws \Plank\Mediable\Exceptions\MediaUpload\FileSizeException If the file is too large
     */
    private function verifyFileSize($size)
    {
        $max = $this->config['max_size'];
        if ($max > 0 && $size > $max) {
            throw FileSizeException::fileIsTooBig($size, $max);
        }

        return $size;
    }

    /**
     * Verify that the intended destination is available and handle any duplications.
     * @param  \Plank\Mediable\Media  $model
     * @return void
     */
    private function verifyDestination(Media $model)
    {
        $storage = $this->filesystem->disk($model->disk);

        if ($storage->has($model->getDiskPath())) {
            $this->handleDuplicate($model);
        }
    }

    /**
     * Decide what to do about duplicated files.
     * @param  \Plank\Mediable\Media  $model
     * @return void
     * @throws \Plank\Mediable\Exceptions\MediaUpload\FileExistsException If directory is not writable or file already exists at the destination and on_duplicate is set to 'error'
     */
    private function handleDuplicate(Media $model)
    {
        switch ($this->config['on_duplicate']) {
            case static::ON_DUPLICATE_ERROR:
                throw FileExistsException::fileExists($model->getDiskPath());
                break;
            case static::ON_DUPLICATE_REPLACE:
                $this->deleteExistingMedia($model);
                break;
            case static::ON_DUPLICATE_INCREMENT:
            default:
                $model->filename = $this->generateUniqueFilename($model);
        }
    }

    /**
     * Delete the media that previously existed at a destination.
     * @param  \Plank\Mediable\Media  $model
     * @return void
     */
    private function deleteExistingMedia(Media $model)
    {
        Media::where('disk', $model->disk)
            ->where('directory', $model->directory)
            ->where('filename', $model->filename)
            ->where('extension', $model->extension)
            ->delete();

        $this->filesystem->disk($model->disk)->delete($model->getDiskPath());
    }

    /**
     * Increment model's filename until one is found that doesn't already exist.
     * @param  \Plank\Mediable\Media $model
     * @return void
     */
    private function generateUniqueFilename(Media $model)
    {
        $storage = $this->filesystem->disk($model->disk);
        $counter = 0;
        do {
            ++$counter;
            $filename = "{$model->filename} ({$counter})";
            $path = "{$model->directory}/{$filename}.{$model->extension}";
        } while ($storage->has($path));

        return $filename;
    }

    /**
     * Generate the model's filename.
     * @return string
     */
    private function generateFilename()
    {
        if ($this->filename) {
            return $this->filename;
        }

        if ($this->hash_filename) {
            return $this->generateHash();
        }

        return $this->sanitizeFileName($this->source->filename());
    }

    /**
     * Calculate hash of source contents.
     * @return string
     */
    private function generateHash()
    {
        $ctx = hash_init('md5');

        // We don't need to read the file contents if the source has a path
        if ($this->source->path()) {
            hash_update_file($ctx, $this->source->path());
        } else {
            hash_update($ctx, $this->source->contents());
        }

        return hash_final($ctx);
    }

    /**
     * Remove any disallowed characters from a directory value.
     * @param  string $path
     * @return string
     */
    private function sanitizePath($path)
    {
        return str_replace(['#', '?', '\\'], '-', $path);
    }

    /**
     * Remove any disallowed characters from a filename.
     * @param  string $file
     * @return string
     */
    private function sanitizeFileName($file)
    {
        return str_replace(['#', '?', '\\', '/'], '-', $file);
    }
}
