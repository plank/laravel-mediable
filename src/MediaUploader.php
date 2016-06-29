<?php

namespace Frasmage\Mediable;

use Frasmage\Mediable\Excpetions\MediaUploadException;
use Frasmage\Mediable\Excpetions\MediaManagerException;
use Frasmage\Mediable\Media;
use Frasmage\Mediable\UploadSourceAdapters\SourceAdapterFactory;
use Frasmage\Mediable\UploadSourceAdapters\SourceAdapterInterface;
use Illuminate\Filesystem\FileSystemManager;
use Storage;

class MediaUploader
{
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
     * Filesystem disk instace
     * @var Illuminate\Filesystem\FileSystem
     */
    private $storage;

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
     * List of media types that this uploader is allowed to handle
     * @var array
     */
    private $allowed_types = [];

    /**
     * List of mime types that this uploader is allowed to handle
     * @var array
     */
    private $allowed_mimes = [];

    /**
     * Lost of file extensions that this uploader is allowed to handle
     * @var array
     */
    private $allowed_extensions = [];

    /**
     * Constructor
     * @param FileSystemManager    $filesystem
     * @param SourceAdapterFactory $factory
     */
    public function __construct(FileSystemManager $filesystem, SourceAdapterFactory $factory, $config)
    {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
        $this->config = $config;
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
        if(!in_array($disk, $this->config['allowed_disks']) || !in_array($disk, config('filesystems.disks'))){
            throw MediaUploadException::diskNotAllowed($disk);
        }
        $this->disk = $disk;
        $this->storage = $this->filesystem->disk($disk);
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
        $this->filename = (string) $filename;
        return $this;
    }

    /**
     * Set a list of MIME types that the source file must be restricted to
     * @param array $allowed_mimes
     * @return static
     */
    public function restrictToMimeTypes($allowed_mimes)
    {
        $this->allowed_mimes = $allowed_mimes;
        return $this;
    }

    /**
     * Set a list of file extensions that the source file must be restricted to
     * @param array $allowed_extensions
     * @return static
     */
    public function restrictToExtensions($allowed_extensions)
    {
        $this->allowed_extensions = $allowed_extensions
        return $this;
    }

    /**
     * Set a list of media types that the source file must be restricted to
     * @param array $allowed_types
     * @return static
     */
    public function retrictToMediaTypes($allowed_types)
    {
        $this->allowed_types = $allowed_types;
        return $this;
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
        $class = $this->config['model'];
        $model = new $class;

        $this->verifySource();
        $this->verifyFileType($model);
        $this->verifyFileSize($model);
        $this->verifyDestination($model);

        $this->storage->put($model->diskPath(), $this->source->contents());
        $model->save();
        return $model;
    }

    /**
     * Ensure that a valid source has been provided
     * @return void
     * @throws MediaUploadException If no source is provided or is invalid
     */
    private function verifySource(){
        if(empty($this->source)){
            throw MediaUploadException::noSourceProvided();
        }
        if(!$this->source->valid()){
            throw MediaUploadException::fileNotFound($this->source->path());
        }
    }

    /**
     * Ensure that the mime_type, extension and media type are allowed
     * @param  Media  $model
     * @return void
     * @throws MediaUploadException If any of the descriptors are not allowed
     */
    private function verifyFileType(Media $model)
    {
        $model->mime_type = $this->source->mimeType();
        $model->extension = $this->source->extension();
        $model->type = $this->inferMediaType($model->mime_type, $model->extension);

        if(!empty($this->allowed_mimes) && !in_array($model->mime_type, $this->allowed_mimes)){
            throw MediaUploadException::mimeRestricted($model->mime_type, $this->allowed_mimes);
        }

        if(!empty($this->allowed_extensions) && !in_array($model->extension, $this->allowed_extensions)){
            throw MediaUploadException::extensionRestricted($model->extension, $this->allowed_extensions);
        }

        if(!$this->config['allow_unrecognized_types']){
            throw MediaUploadException::unrecognizedFileType($model->mime_type, $model->extension);
        }

        if(!empty($this->allowed_types) && !in_array($model->type, $this->allowed_types)){
            throw MediaUploadException::typeRestricted($model->type, $this->allowed_types);
        }
    }

    /**
     * Determine the media type of the file based on the MIME type and the extension
     * @param  string $mime
     * @param  string $extension
     * @param  boolean|null $strict Defaults to mediable.strict_type_checking value
     * @return string
     * @throws  MediaUploadException If strict mode is enabled and mime and extension disagree
     */
    public function inferMediaType($mime, $extension, $strict = null)
    {
        $strict = is_null($strict) ? $this->config['strict_type_checking'] : $strict;
        $type_from_mime = $this->inferMediaTypeFromMime($mime);
        $type_from_ext = $this->inferMediaTypeFromExtension($extension);

        if($strict && $type_from_mime != $type_from_ext){
            throw MediaUploadException::strictTypeMismatch($mime, $extension);
        }

        if($type_from_mime != Media::TYPE_OTHER){
            return $type_from_mime;
        }

        return $type_from_ext;
    }

    /**
     * Determine the media type of the file based on the MIME type
     * @param  string $mime
     * @return string
     */
    public function inferMediaTypeFromMime($mime)
    {
        foreach($this->config['types'] as $type => $attributes){
            if(in_array($mime, $attributes['mime_types'])){
                return $type;
            }
        }
        return Media::TYPE_OTHER;
    }

    /**
     * Determine the media type of the file based on the extension
     * @param  string $extension
     * @return string|null
     */
    public function inferMediaTypeFromExtension($extension)
    {
        foreach($this->config['types'] as $type => $attributes){
            if(in_array($extension, $attributes['extensions'])){
                return $type;
            }
        }
        return Media::TYPE_OTHER;
    }

    /**
     * Verify that the file being uploaded is not larger than the maximum
     * @param  Media $model
     * @return void
     * @throws MediaUploadException If the file is too large
     */
    private function verifyFileSize(Media $model)
    {
        $model->size = $this->source->size();
        $max = $this->config['max_size'];
        if($max > 0 && $this->size > $max){
            throw MediaUploadException::fileIsTooBig($this->size);
        }
    }

    /**
     * Verify that the intended destination is available and handle any duplications
     * @param  Media  $model
     * @return void
     * @throws MediaUploadException If directory is not writable or file already exists at the destination and on_duplicate is set to 'error'
     */
    private function verifyDestination(Media $model)
    {
        if(empty($this->disk)){
            $this->setDisk($this->config['default_disk']);
        }
        $filename = empty($this->filename) ? $this->source->filename() : $this->filename

        $model->disk = $this->disk;
        $model->directory = $this->directory;
        $model->filename = $this->sanitizeFileName($filename);

        if(!$this->storage->isWritable($model->directory)){
            throw MediaUploadException::directoryNotWritable($model->disk, $model->directory);
        }

        if($this->storage->has($model->diskPath())){
            switch($this->config['on_duplicate']){
                case 'error':
                    throw MediaUploadException::fileExists($model->diskpath);
                    break;
                case 'replace':
                    $this->deleteExistingMedia($model);
                    break;
                case 'increment':
                default:
                    $this->generateUniqueFilename($model);
            }
        }

    }

    /**
     * Delete the media that previously existed at a destination
     * @param  Media  $model
     * @return void
     */
    private function deleteExistingMedia(Media $model)
    {
        $this->storage->delete($model->diskPath());
        Media::where('disk', $model->disk)
            ->where('directory', $model->directory)
            ->where('filename', $model->filename())
            ->where('extension', $model->extension())
            ->delete();
    }

    /**
     * Increment model's filename until one is found that doesn't already exist
     * @param  Media $model
     * @return void
     */
    private function generateUniqueFilename(Media $model)
    {
        $counter = 0;
        do(){
            ++$counter;
            $filename = "{$model->filename} ({$counter})"
            $path = "{$model->directory}/{$filename}.{$model->extension}";
        }while($this->storage->has($path));
        $model->filename = $filename;
    }

    /**
     * Remove any disallowed characters from a directory value
     * @param  string $path
     * @return string
     */
    private function sanitizePath($path){
        return str_replace(['#', '?', '\\'], '-', $path);
    }

    /**
     * Remove any disallowed characters from a filename
     * @param  string $file
     * @return string
     */
    private function sanitizeFileName($file){
        return str_replace(['#', '?', '\\', '/'], '-', $file);
    }

}
