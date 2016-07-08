<?php

namespace Plank\Mediable;

use Plank\Mediable\Exceptions\MediaUploadException;
use Plank\Mediable\Exceptions\MediaManagerException;
use Plank\Mediable\Media;
use Plank\Mediable\SourceAdapters\SourceAdapterFactory;
use Plank\Mediable\SourceAdapters\SourceAdapterInterface;
use Illuminate\Filesystem\FilesystemManager;
use Storage;

/**
 * Media Uploader
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
     * Mediable configurations
     * @var array
     */
    private $config;

    /**
     * Source adapter
     * @var
     */
    private $source;

    /**
     * Name of the filesystem disk
     * @var string
     */
    private $disk;

    /**
     * Path relative to the filesystem disk root
     * @var string
     */
    private $directory = '';

    /**
     * Name of the new file
     * @var string
     */
    private $filename;

    /**
     * Constructor
     * @param FileSystemManager    $filesystem
     * @param SourceAdapterFactory $factory
     */
    public function __construct(FileSystemManager $filesystem, SourceAdapterFactory $factory, $config = null)
    {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
        $this->config = $config ?: config('mediable');
    }

    /**
     * Set the source for the file
     * @param  mixed $source
     * @return static
     */
    public function fromSource($source)
    {
        $this->source = $this->factory->create($source);
        return $this;
    }

    /**
     * Set the filesystem disk and relative directory where the file will be saved
     * @param  string $disk
     * @param  string $directory
     * @return static
     */
    public function toDestination($disk, $directory)
    {
        return $this->setDisk($disk)->setDirectory($directory);
    }

    /**
     * Set the filesystem disk on which the file will be saved
     * @param string $disk
     * @return static
     * @throws MediaUploadException if the disk is not found or not allowed for upload
     */
    public function setDisk($disk)
    {
        if (!array_key_exists($disk, config('filesystems.disks'))) {
            throw MediaUploadException::diskNotFound($disk);
        }

        if (!in_array($disk, $this->config['allowed_disks'])) {
            throw MediaUploadException::diskNotAllowed($disk);
        }
        $this->disk = $disk;
        return $this;
    }

    /**
     * Set the directory relative to the filesystem disk at which the file will be saved
     * @param string $directory
     * @return static
     */
    public function setDirectory($directory)
    {
        $this->directory = trim($this->sanitizePath($directory), DIRECTORY_SEPARATOR);
        return $this;
    }

    /**
     * Override the filename of the source file
     * @param string $filename
     * @return static
     */
    public function setFilename($filename)
    {
        $this->filename = $this->sanitizeFilename($filename);
        return $this;
    }

    /**
     * Change the class to use for generated Media
     * @param string $class
     * @return static
     * @throws MediaUploaderException if $class does not extend Plank\Mediable\Media
     */
    public function setModelClass($class)
    {
        if (!is_subclass_of($class, Media::class)) {
            throw MediaUploadException::cannotSetModel($class);
        }
        $this->config['model'] = $class;
        return $this;
    }

    /**
     * Change the maximum allowed filesize
     * @param integer $size
     * @return static
     */
    public function setMaximumSize($size)
    {
        $this->config['max_size'] = (int) $size;
        return $this;
    }

    /**
     * Change the behaviour for when the destination already exists
     * @param string $behavior
     * @return static
     */
    public function setOnDuplicateBehavior($behavior)
    {
        $this->config['on_duplicate'] = (string) $behavior;
        return $this;
    }

    /**
     * Change whether mime and extensions must agree
     * @param boolean $strict
     * @return static
     */
    public function setStrictTypeChecking($strict)
    {
        $this->config['strict_type_checking'] = (boolean) $strict;
        return $this;
    }

    /**
     * Change whether unknown media types are allowed
     * @param boolean $allow
     * @return static
     */
    public function setAllowUnrecognizedTypes($allow)
    {
        $this->config['allow_unrecognized_types'] = (boolean) $allow;
        return $this;
    }

    /**
     * Add or update the definition of a media type
     * @param string $type       the name of the type
     * @param array  $mime_types list of MIME types recognized
     * @param array  $extensions list of file extensions recognized
     * @return static
     */
    public function setTypeDefinition($type, $mime_types, $extensions)
    {
        $this->config['types'][$type] = [
            'mime_types' => (array) $mime_types,
            'extensions' => (array) $extensions,
        ];
        return $this;
    }

    /**
     * Set a list of MIME types that the source file must be restricted to
     * @param array $allowed_mimes
     * @return static
     */
    public function setAllowedMimeTypes($allowed_mimes)
    {
        $this->config['allowed_mime_types'] = $allowed_mimes;
        return $this;
    }

    /**
     * Set a list of file extensions that the source file must be restricted to
     * @param array $allowed_extensions
     * @return static
     */
    public function setAllowedExtensions($allowed_extensions)
    {
        $this->config['allowed_extensions'] = $allowed_extensions;
        return $this;
    }

    /**
     * Set a list of aggregate types that the source file must be restricted to
     * @param array $allowed_types
     * @return static
     */
    public function setAllowedAggregateTypes($allowed_types)
    {
        $this->config['allowed_types'] = $allowed_types;
        return $this;
    }

    /**
     * Determine the aggregate type of the file based on the MIME type and the extension
     * @param  string $mime_type
     * @param  string $extension
     * @param  boolean|null $strict Defaults to mediable.strict_type_checking value
     * @return string
     * @throws  MediaUploadException If strict mode is enabled and mime and extension disagree
     */
    public function inferAggregateType($mime_type, $extension, $strict = null)
    {
        $strict = is_null($strict) ? $this->config['strict_type_checking'] : $strict;
        $types_for_mime = $this->possibleAggregateTypesForMimeType($mime_type);
        $types_for_extension = $this->possibleAggregateTypesForExtension($extension);

        $intersection = array_intersect($types_for_mime, $types_for_extension);

        if (count($intersection)) {
            return $intersection[0];
        }

        if (empty($types_for_mime) && empty($types_for_extension)) {
            return Media::TYPE_OTHER;
        }

        if ($strict) {
            throw MediaUploadException::strictTypeMismatch($mime_type, $extension);
        }

        $merged = array_merge($types_for_mime, $types_for_extension);
        return reset($merged);
    }

    /**
     * Determine the aggregate type of the file based on the MIME type
     * @param  string $mime
     * @return string
     */
    public function possibleAggregateTypesForMimeType($mime)
    {
        $types = [];
        foreach ($this->config['types'] as $type => $attributes) {
            if (in_array($mime, $attributes['mime_types'])) {
                $types[] = $type;
            }
        }
        return $types;
    }

    /**
     * Determine the aggregate type of the file based on the extension
     * @param  string $extension
     * @return string|null
     */
    public function possibleAggregateTypesForExtension($extension)
    {
        $types = [];
        foreach ($this->config['types'] as $type => $attributes) {
            if (in_array($extension, $attributes['extensions'])) {
                $types[] = $type;
            }
        }
        return $types;
    }

    /**
     * Process the file upload
     *
     * Validates the source, then stores the file onto the disk and creates and stores a new Media instance.
     * @return Media
     * @throws MediaUploadException If any validation checks fail
     */
    public function upload()
    {
        $this->verifySource();

        $model = $this->makeModel();

        $model->size = $this->verifyFileSize($this->source->size());
        $model->mime_type = $this->verifyMimeType($this->source->mimeType());
        $model->extension = $this->verifyExtension($this->source->extension());

        $type = $this->inferAggregateType($model->mime_type, $model->extension);
        $model->type = $this->verifyAggregateType($type, $model->mime_type, $model->extension);


        $model->disk = $this->disk ?: $this->config['default_disk'];
        $model->directory = $this->directory;
        $model->filename = $this->filename ?: $this->sanitizeFilename($source->filename());

        $this->verifyDestination($model);

        $this->filesystem->disk($model->disk)->put($model->getDiskPath(), $this->source->contents());
        $model->save();
        return $model;
    }

    private function makeModel()
    {
        $class = $this->config['model'];
        return new $class;
    }

    /**
     * Ensure that a valid source has been provided
     * @return void
     * @throws MediaUploadException If no source is provided or is invalid
     */
    private function verifySource()
    {
        if (empty($this->source)) {
            throw MediaUploadException::noSourceProvided();
        }
        if (!$this->source->valid()) {
            throw MediaUploadException::fileNotFound($this->source->path());
        }
    }

    /**
     * Ensure that the file's mime type is allowed
     * @param  string $mime_type
     * @return string
     */
    private function verifyMimeType($mime_type)
    {
        $allowed = $this->config['allowed_mime_types'];
        if (!empty($allowed) && !in_array($mime_type, $allowed)) {
            throw MediaUploadException::mimeRestricted($mime_type, $allowed);
        }
        return $mime_type;
    }

    /**
     * Ensure that the file's extension is allowed
     * @param  string $extension
     * @return string
     */
    private function verifyExtension($extension)
    {
        $allowed = $this->config['allowed_extensions'];
        if (!empty($allowed) && !in_array($extension, $allowed)) {
            throw MediaUploadException::extensionRestricted($extension, $allowed);
        }
        return $extension;
    }

    /**
     * Ensure that the file's aggregate type is allowed
     * @param  string $type
     * @param  string $mime_type
     * @param  string $extension
     * @return string
     */
    private function verifyAggregateType($type, $mime_type, $extension)
    {
        if (!$this->config['allow_unrecognized_types'] && $type == Media::TYPE_OTHER) {
            throw MediaUploadException::unrecognizedFileType($mime_type, $extension);
        }

        $allowed = $this->config['allowed_types'];
        if (!empty($allowed) && !in_array($type, $allowed)) {
            throw MediaUploadException::typeRestricted($type, $allowed);
        }
        return $type;
    }

    /**
     * Verify that the file being uploaded is not larger than the maximum
     * @param  Media $model
     * @return integer
     * @throws MediaUploadException If the file is too large
     */
    private function verifyFileSize($size)
    {
        $max = $this->config['max_size'];
        if ($max > 0 && $size > $max) {
            throw MediaUploadException::fileIsTooBig($size, $max);
        }
        return $size;
    }

    /**
     * Verify that the intended destination is available and handle any duplications
     * @param  Media  $model
     * @return void
     * @throws MediaUploadException If directory is not writable or file already exists at the destination and on_duplicate is set to 'error'
     */
    private function verifyDestination(Media $model)
    {
        $storage = $this->filesystem->disk($model->disk);

        if ($storage->has($model->getDiskPath())) {
            $this->handleDuplicate($model);
        }
    }

    private function handleDuplicate(Media $model)
    {
        switch ($this->config['on_duplicate']) {
            case static::ON_DUPLICATE_ERROR:
                throw MediaUploadException::fileExists($model->getDiskpath);
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
     * Delete the media that previously existed at a destination
     * @param  Media  $model
     * @return void
     */
    private function deleteExistingMedia(Media $model)
    {
        Media::where('disk', $model->disk)
            ->where('directory', $model->directory)
            ->where('filename', $model->filename)
            ->where('extension', $model->extension)
            ->first()->delete();
    }

    /**
     * Increment model's filename until one is found that doesn't already exist
     * @param  Media $model
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
     * Remove any disallowed characters from a directory value
     * @param  string $path
     * @return string
     */
    private function sanitizePath($path)
    {
        return str_replace(['#', '?', '\\'], '-', $path);
    }

    /**
     * Remove any disallowed characters from a filename
     * @param  string $file
     * @return string
     */
    private function sanitizeFileName($file)
    {
        return str_replace(['#', '?', '\\', '/'], '-', $file);
    }
}
