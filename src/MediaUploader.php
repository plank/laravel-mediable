<?php
declare(strict_types=1);

namespace Plank\Mediable;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use League\Flysystem\UnableToRetrieveMetadata;
use Plank\Mediable\Enum\OnDuplicateBehaviour;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\Exceptions\MediaUpload\FileExistsException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotFoundException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException;
use Plank\Mediable\Exceptions\MediaUpload\FileSizeException;
use Plank\Mediable\Exceptions\MediaUpload\ForbiddenException;
use Plank\Mediable\Exceptions\MediaUpload\InvalidHashException;
use Plank\Mediable\FileSanitizers\SanitizerInterface;
use Plank\Mediable\Helpers\File;
use Plank\Mediable\SourceAdapters\RawContentAdapter;
use Plank\Mediable\SourceAdapters\SourceAdapterFactory;
use Plank\Mediable\SourceAdapters\SourceAdapterInterface;
use Plank\Mediable\SourceAdapters\StreamAdapter;

/**
 * Media Uploader.
 *
 * Validates files, uploads them to disk and generates Media
 */
class MediaUploader
{
    private FileSystemManager $filesystem;

    private SourceAdapterFactory $factory;

    private ImageManipulator $imageManipulator;

    private MediaUploaderConfiguration $config;

    private SourceAdapterInterface $source;

    /**
     * Constructor.
     * @param FilesystemManager $filesystem
     * @param SourceAdapterFactory $factory
     * @param ImageManipulator $imageManipulator
     * @param MediaUploaderConfiguration $configuration
     */
    public function __construct(
        FileSystemManager $filesystem,
        SourceAdapterFactory $factory,
        ImageManipulator $imageManipulator,
        MediaUploaderConfiguration $configuration
    ) {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
        $this->imageManipulator = $imageManipulator;
        $this->config = $configuration;
    }

    /**
     * Set the source for the file.
     *
     * @param  mixed $source
     *
     * @return $this
     * @throws ConfigurationException
     */
    public function fromSource(mixed $source): self
    {
        $this->source = $this->factory->create($source);

        return $this;
    }

    /**
     * Set the source for the string data.
     * @param  string $source
     * @return $this
     */
    public function fromString(string $source): self
    {
        $this->source = new RawContentAdapter($source);

        return $this;
    }

    /**
     * Set the filesystem disk and relative directory where the file will be saved.
     *
     * @param  string $disk
     * @param  string $directory
     *
     * @return $this
     * @throws ConfigurationException
     * @throws ForbiddenException
     */
    public function toDestination(string $disk, string $directory): self
    {
        return $this->toDisk($disk)->toDirectory($directory);
    }

    /**
     * Set the filesystem disk on which the file will be saved.
     *
     * @param string $disk
     *
     * @return $this
     * @throws ConfigurationException
     * @throws ForbiddenException
     */
    public function toDisk(string $disk): self
    {
        $this->config->destinationDisk = $this->verifyDisk($disk);

        return $this;
    }

    /**
     * Set the directory relative to the filesystem disk at which the file will be saved.
     * @param string $directory
     * @return $this
     */
    public function toDirectory(string $directory): self
    {
        $this->config->destinationDirectory = File::sanitizePath($directory);

        return $this;
    }

    /**
     * Specify the filename to copy to the file to.
     * @param string $filename
     * @return $this
     */
    public function useFilename(string $filename): self
    {
        $this->config->destinationFilename = File::sanitizeFilename(
            $filename,
            null,
            $this->config->forbiddenExtensions
        );
        $this->config->hashFilenameAlgorithm = null;

        return $this;
    }

    public function withAltAttribute(string $alt): self
    {
        $this->config->fileAlternativeText = $alt;
        return $this;
    }

    /**
     * Indicates to the uploader to generate a filename using the file's MD5 hash.
     * @param string $algo any hashing algorithm supported by PHP's hash() function
     * @return $this
     */
    public function useHashForFilename(string $algo = 'md5'): self
    {
        $this->config->hashFilenameAlgorithm = $algo;
        $this->config->destinationFilename = null;

        return $this;
    }

    /**
     * Restore the default behaviour of using the source file's filename.
     * @return $this
     */
    public function useOriginalFilename(): self
    {
        $this->config->destinationFilename = null;
        $this->config->hashFilenameAlgorithm = null;

        return $this;
    }

    /**
     * Change the class to use for generated Media.
     * @param string $class
     * @return $this
     * @throws ConfigurationException if $class does not extend Plank\Mediable\Media
     */
    public function setModelClass(string $class): self
    {
        if (!is_subclass_of($class, Media::class)) {
            throw ConfigurationException::cannotSetModel($class);
        }
        $this->config->modelClass = $class;

        return $this;
    }

    /**
     * Change the maximum allowed file size.
     * @param int $size
     * @return $this
     */
    public function setMaximumSize(int $size): self
    {
        $this->config->maxUploadSize = $size;

        return $this;
    }

    /**
     * Change the behaviour for when a file already exists at the destination.
     * @param OnDuplicateBehaviour $behavior
     * @return $this
     */
    public function setOnDuplicateBehavior(OnDuplicateBehaviour $behavior): self
    {
        $this->config->onDuplicate = $behavior;

        return $this;
    }

    /**
     * Get current behavior when duplicate file is uploaded.
     *
     * @return OnDuplicateBehaviour
     */
    public function getOnDuplicateBehavior(): OnDuplicateBehaviour
    {
        return $this->config->onDuplicate;
    }

    /**
     * Throw an exception when file already exists at the destination.
     *
     * @return $this
     */
    public function onDuplicateError(): self
    {
        return $this->setOnDuplicateBehavior(OnDuplicateBehaviour::Error);
    }

    /**
     * Append incremented counter to file name when file already exists at destination.
     *
     * @return $this
     */
    public function onDuplicateIncrement(): self
    {
        return $this->setOnDuplicateBehavior(OnDuplicateBehaviour::Increment);
    }

    /**
     * Overwrite existing Media when file already exists at destination.
     *
     * This will delete the old media record and create a new one, detaching any existing associations.
     *
     * @return $this
     */
    public function onDuplicateReplace(): self
    {
        return $this->setOnDuplicateBehavior(OnDuplicateBehaviour::Replace);
    }

    /**
     * Overwrite existing Media when file already exists at destination and delete any variants of the original record.
     *
     * This will delete the old media record and create a new one, detaching any existing associations.
     *
     * This will also delete any existing
     *
     * @return $this
     */
    public function onDuplicateReplaceWithVariants(): self
    {
        return $this->setOnDuplicateBehavior(OnDuplicateBehaviour::ReplaceWithVariants);
    }

    /**
     * Overwrite existing files and update the existing media record.
     *
     * This will retain any existing associations.
     *
     * @return $this
     */
    public function onDuplicateUpdate(): self
    {
        return $this->setOnDuplicateBehavior(OnDuplicateBehaviour::Update);
    }

    /**
     * Change whether both the MIME type and extensions must match the same aggregate type.
     * @param bool $strict
     * @return $this
     */
    public function setStrictTypeChecking(bool $strict): self
    {
        $this->config->strictTypeChecking = $strict;

        return $this;
    }

    /**
     * Change whether files not matching any aggregate types are allowed.
     * @param bool $allow
     * @return $this
     */
    public function setAllowUnrecognizedTypes(bool $allow): self
    {
        $this->config->allowUnrecognizedTypes = $allow;

        return $this;
    }

    /**
     * Add or update the definition of a aggregate type.
     * @param string $type the name of the type
     * @param string[] $mimeTypes list of MIME types recognized
     * @param string[] $extensions list of file extensions recognized
     * @return $this
     */
    public function setTypeDefinition(string $type, array $mimeTypes, array $extensions): self
    {
        $this->config->definedAggregateTypes[$type] = [
            'mime_types' => array_map('strtolower', $mimeTypes),
            'extensions' => array_map('strtolower', $extensions),
        ];

        return $this;
    }

    /**
     * Set a list of MIME types that the source file must be restricted to.
     * @param string[] $allowedMimes
     * @return $this
     */
    public function setAllowedMimeTypes(array $allowedMimes): self
    {
        $this->config->allowedMimeTypes = array_map('strtolower', $allowedMimes);

        return $this;
    }

    public function setForbiddenMimeTypes(array $forbiddenMimes): self
    {
        $this->config->forbiddenMimeTypes = array_map('strtolower', $forbiddenMimes);

        return $this;
    }

    /**
     * Prefer the MIME type provided by the client, if any, over the inferred MIME type.
     * Depending on the source, this may not be accurate.
     * @return $this
     */
    public function preferClientMimeType(): self
    {
        $this->config->preferClientMimeType = true;

        return $this;
    }

    /**
     * Prefer the MIME type inferred by the contents of the file, if available,
     * over the MIME type provided by the client.
     * @return $this
     */
    public function preferInferredMimeType(): self
    {
        $this->config->preferClientMimeType = false;

        return $this;
    }

    /**
     * Set a list of file extensions that the source file must be restricted to.
     * @param string[] $allowedExtensions
     * @return $this
     */
    public function setAllowedExtensions(array $allowedExtensions): self
    {
        $this->config->allowedExtensions = array_map('strtolower', $allowedExtensions);

        return $this;
    }

    public function setForbiddenExtensions(array $forbiddenExtensions): self
    {
        $this->config->forbiddenExtensions = array_map('strtolower', $forbiddenExtensions);

        return $this;
    }

    /**
     * Set a list of aggregate types that the source file must be restricted to.
     * @param string[] $allowedTypes
     * @return $this
     */
    public function setAllowedAggregateTypes(array $allowedTypes): self
    {
        $this->config->allowedAggregateTypes = $allowedTypes;

        return $this;
    }

    /**
     * Verify the MD5 hash of the file contents matches an expected value.
     * The upload process will throw an InvalidHashException if the hash of the
     * uploaded file does not match the provided value.
     * @param string|null $expectedHash set to null to disable hash validation
     * @param string $algo any hashing algorithm supported by PHP's hash() function
     * @return $this
     */
    public function validateHash(?string $expectedHash, string $algo = 'md5'): self
    {
        $this->config->expectedHashes[$algo] = $expectedHash;
        return $this;
    }

    /**
     * Make the resulting file public (default behaviour)
     * @return $this
     */
    public function makePublic(): self
    {
        $this->config->fileVisibility = Filesystem::VISIBILITY_PUBLIC;
        return $this;
    }

    /**
     * Make the resulting file private
     * @return $this
     */
    public function makePrivate(): self
    {
        $this->config->fileVisibility = Filesystem::VISIBILITY_PRIVATE;
        return $this;
    }

    public function getVisibility(): string
    {
        if ($this->config->fileVisibility) {
            return $this->config->fileVisibility;
        }

        return config(
            'filesystems.disks.'.$this->config->destinationDisk.'.visibility',
            Filesystem::VISIBILITY_PUBLIC
        );
    }

    /**
     * Apply an image manipulation to the uploaded image.
     *
     * This will modify the image before saving it to disk.
     * The original image will not be preserved.
     *
     * Note this will manipulate the image as part of the upload process, which may be slow.
     * @param string|ImageManipulation $imageManipulation Either a defined ImageManipulation variant name
     *   or an ImageManipulation instance
     * @return $this
     */
    public function applyImageManipulation(string|ImageManipulation $imageManipulation): self
    {
        if (is_string($imageManipulation)) {
            $imageManipulation = $this->imageManipulator->getVariantDefinition($imageManipulation);
        }
        $this->config->imageManipulation = $imageManipulation;
        return $this;
    }

    /**
     * Additional options to pass to the filesystem when uploading
     * @param array $options
     * @return $this
     */
    public function withOptions(array $options): self
    {
        $this->config->filesystemOptions = $options;
        return $this;
    }

    /**
     * Determine the aggregate type of the file based on the MIME type and the extension.
     * @param  string $mimeType
     * @param  string $extension
     * @return string
     * @throws FileNotSupportedException If the file type is not recognized
     * @throws FileNotSupportedException If the file type is restricted
     * @throws FileNotSupportedException If the aggregate type is restricted
     */
    public function inferAggregateType(string $mimeType, string $extension): string
    {
        $mimeType = strtolower($mimeType);
        $extension = strtolower($extension);
        $allowedTypes = $this->config->allowedAggregateTypes;
        $typesForMime = $this->possibleAggregateTypesForMimeType($mimeType);
        $typesForExtension = $this->possibleAggregateTypesForExtension($extension);

        if (count($allowedTypes)) {
            $intersection = array_intersect($typesForMime, $typesForExtension, $allowedTypes);
        } else {
            $intersection = array_intersect($typesForMime, $typesForExtension);
        }

        if (count($intersection)) {
            $type = Arr::first($intersection);
        } elseif (empty($typesForMime) && empty($typesForExtension)) {
            if (!$this->config->allowUnrecognizedTypes) {
                throw FileNotSupportedException::unrecognizedFileType($mimeType, $extension);
            }
            $type = Media::TYPE_OTHER;
        } else {
            if ($this->config->strictTypeChecking) {
                throw FileNotSupportedException::strictTypeMismatch($mimeType, $extension);
            }
            $merged = array_merge($typesForMime, $typesForExtension);
            $type = reset($merged);
        }

        if (count($allowedTypes) && !in_array($type, $allowedTypes)) {
            throw FileNotSupportedException::aggregateTypeRestricted($type, $allowedTypes);
        }

        return $type;
    }

    /**
     * Determine the aggregate type of the file based on the MIME type.
     * @param  string $mime
     * @return string[]
     */
    public function possibleAggregateTypesForMimeType(string $mime): array
    {
        $types = [];
        foreach ($this->config->definedAggregateTypes as $type => $attributes) {
            if (in_array($mime, $attributes['mime_types'])) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * Determine the aggregate type of the file based on the extension.
     * @param  string $extension
     * @return string[]
     */
    public function possibleAggregateTypesForExtension(string $extension): array
    {
        $types = [];
        foreach ($this->config->definedAggregateTypes ?? [] as $type => $attributes) {
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
     *
     * @return Media
     * @throws ConfigurationException
     * @throws FileExistsException
     * @throws FileNotFoundException
     * @throws FileNotSupportedException
     * @throws FileSizeException
     * @throws InvalidHashException
     */
    public function upload(): Media
    {
        $this->verifyFile();

        $model = $this->populateModel($this->makeModel());

        $this->sanitizeFile($model);
        $this->manipulateImage($model);

        if ($this->config->beforeSaveCallback) {
            call_user_func($this->config->beforeSaveCallback, $model, $this->source);
        }

        $this->verifyDestination($model);
        $this->writeToDisk($model);
        $model->save();

        return $model;
    }

    /**
     * Process the file upload, overwriting an existing media's file
     *
     * Uploader will automatically place the file on the same disk as the original media.
     *
     * @param  Media $media
     * @return Media
     *
     * @throws ConfigurationException
     * @throws FileNotFoundException
     * @throws FileNotSupportedException
     * @throws FileSizeException
     * @throws ForbiddenException
     * @throws FileExistsException
     */
    public function replace(Media $media): Media
    {
        if (!$this->config->destinationDisk) {
            $this->toDisk($media->disk);
        }

        if (!$this->config->destinationDirectory) {
            $this->toDirectory($media->directory);
        }

        if (!$this->config->destinationFilename) {
            $this->useFilename($media->filename);
        }

        // Remember original file location.
        // We will only delete it if validation passes
        $disk = $media->disk;
        $path = $media->getDiskPath();

        $model = $this->populateModel($media);
        $this->sanitizeFile($model);

        if ($this->config->beforeSaveCallback) {
            call_user_func($this->config->beforeSaveCallback, $model, $this->source);
        }


        $this->verifyDestination($model);
        // Delete original file, if necessary
        $this->filesystem->disk($disk)->delete($path);
        $this->writeToDisk($model);

        $model->save();

        return $model;
    }

    /**
     * Validate input and convert to Media attributes
     * @param  Media $model
     * @return Media
     *
     * @throws ConfigurationException
     * @throws FileNotFoundException
     * @throws FileNotSupportedException
     * @throws FileSizeException
     */
    private function populateModel(Media $model): Media
    {
        $model->size = $this->verifyFileSize($this->source->size() ?? 0);
        $model->mime_type = $this->verifyMimeType($this->selectMimeType());
        $model->extension = $this->verifyExtension(
            $this->source->extension()
                ?? File::guessExtension($model->mime_type)
        );
        $model->aggregate_type = $this->inferAggregateType($model->mime_type, $model->extension);

        $model->disk = $this->config->destinationDisk ?? $this->config->defaultDisk;
        $model->directory = $this->config->destinationDirectory;
        $model->filename = $this->generateFilename();

        if ($this->config->fileAlternativeText) {
            $model->alt = $this->config->fileAlternativeText;
        }

        return $model;
    }

    /**
     * Set the before save callback
     * @param \Closure $callable
     * @return $this
     */
    public function beforeSave(\Closure $callable): self
    {
        $this->config->beforeSaveCallback = $callable;
        return $this;
    }

    /**
     * Create a `Media` record for a file already on a disk.
     *
     * @param  string $disk
     * @param  string $path Path to file, relative to disk root
     *
     * @return Media
     * @throws ConfigurationException
     * @throws FileNotFoundException
     * @throws FileNotSupportedException
     * @throws FileSizeException
     * @throws ForbiddenException
     */
    public function importPath(string $disk, string $path): Media
    {
        $directory = File::cleanDirname($path);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $this->import($disk, $directory, $filename, $extension);
    }

    /**
     * Create a `Media` record for a file already on a disk.
     *
     * @param  string $disk
     * @param  string $directory
     * @param  string $filename
     * @param  string $extension
     *
     * @return Media
     * @throws ConfigurationException
     * @throws FileNotFoundException If the file does not exist
     * @throws FileNotSupportedException
     * @throws FileSizeException
     * @throws ForbiddenException
     */
    public function import(string $disk, string $directory, string $filename, string $extension): Media
    {
        $disk = $this->verifyDisk($disk);
        $storage = $this->filesystem->disk($disk);

        $model = $this->makeModel();
        $model->disk = $disk;
        $model->directory = $directory;
        $model->filename = $filename;
        $model->extension = $this->verifyExtension($extension, false);

        if (!$storage->exists($model->getDiskPath())) {
            throw FileNotFoundException::fileNotFound($model->getDiskPath());
        }

        $model->mime_type = $this->verifyMimeType(
            $this->inferMimeType($storage, $model->getDiskPath())
        );
        $model->aggregate_type = $this->inferAggregateType($model->mime_type, $model->extension);
        $model->size = $this->verifyFileSize($storage->size($model->getDiskPath()));

        if ($this->config->fileVisibility) {
            $storage->setVisibility($model->getDiskPath(), $this->config->fileVisibility);
        }

        if ($this->config->fileAlternativeText) {
            $model->alt = $this->config->fileAlternativeText;
        }

        if ($this->config->beforeSaveCallback) {
            call_user_func($this->config->beforeSaveCallback, $model, $this->source);
        }

        $model->save();

        return $model;
    }

    /**
     * Reanalyze a media record's file and adjust the aggregate type and size, if necessary.
     *
     * @param  Media $media
     *
     * @return bool Whether the model was modified
     * @throws FileNotSupportedException
     * @throws FileSizeException
     */
    public function update(Media $media):  bool
    {
        $storage = $this->filesystem->disk($media->disk);

        $media->size = $this->verifyFileSize($storage->size($media->getDiskPath()));
        $media->mime_type = $this->verifyMimeType(
            $this->inferMimeType($storage, $media->getDiskPath())
        );
        $media->aggregate_type = $this->inferAggregateType($media->mime_type, $media->extension);

        if ($this->config->fileAlternativeText) {
            $media->alt = $this->config->fileAlternativeText;
        }

        if ($dirty = $media->isDirty()) {
            $media->save();
        }

        return $dirty;
    }

    /**
     * Verify if file is valid
     * @throws ConfigurationException If no source is provided
     * @throws FileNotFoundException If the source is invalid
     * @throws FileSizeException If the file is too large
     * @throws FileNotSupportedException If the mime type is not allowed
     * @throws FileNotSupportedException If the file extension is not allowed
     * @return void
     */
    public function verifyFile(): void
    {
        $this->verifySource();
        $this->verifyFileSize($this->source->size() ?? 0);
        $mimeType = $this->verifyMimeType(
            $this->selectMimeType()
        );
        $this->verifyExtension(
            $this->source->extension() ?? File::guessExtension($mimeType)
        );

        $this->verifyHashes();
    }

    /**
     * Generate an instance of the `Media` class.
     * @return Media
     */
    private function makeModel(): Media
    {
        $class = $this->config->modelClass;

        return new $class;
    }

    /**
     * Ensure that the provided filesystem disk name exists and is allowed.
     * @param  string $disk
     * @return string
     * @throws ConfigurationException If the disk does not exist
     * @throws ForbiddenException If the disk is not included in the `allowed_disks` config.
     */
    private function verifyDisk(string $disk): string
    {
        if (!array_key_exists($disk, config('filesystems.disks', []))) {
            throw ConfigurationException::diskNotFound($disk);
        }

        if (!in_array($disk, $this->config->allowedDisks)) {
            throw ForbiddenException::diskNotAllowed($disk);
        }

        return $disk;
    }

    /**
     * Ensure that a valid source has been provided.
     * @return void
     * @throws ConfigurationException If no source is provided
     */
    private function verifySource(): void
    {
        if (empty($this->source)) {
            throw ConfigurationException::noSourceProvided();
        }
    }

    private function inferMimeType(Filesystem $filesystem, string $path): string
    {
        $mimeType = null;
        try {
            if (method_exists($filesystem, 'mimeType')) {
                $mimeType = $filesystem->mimeType($path);
            }
        } catch (UnableToRetrieveMetadata $e) {
            // previous versions of flysystem would default to octet-stream when
            // the file was unrecognized. Maintain the behaviour for now
            return 'application/octet-stream';
        }
        return $mimeType ?: 'application/octet-stream';
    }

    private function selectMimeType(): string
    {
        if ($this->config->preferClientMimeType) {
            return $this->source->clientMimeType() ?? $this->source->mimeType();
        }
        return $this->source->mimeType();
    }

    /**
     * Ensure that the file's mime type is allowed.
     * @param  string $mimeType
     * @return string
     * @throws FileNotSupportedException If the mime type is not allowed
     */
    private function verifyMimeType(string $mimeType): string
    {
        $mimeType = strtolower($mimeType);
        $allowed = $this->config->allowedMimeTypes;
        $forbidden = $this->config->forbiddenMimeTypes;
        $actuallyAllowed = array_diff($allowed, $forbidden);
        if (!empty($allowed) && !in_array($mimeType, $actuallyAllowed)) {
            throw FileNotSupportedException::mimeRestricted($mimeType, $actuallyAllowed);
        }
        if (empty($allowed) && in_array($mimeType, $forbidden)) {
            throw FileNotSupportedException::mimeRestricted($mimeType, $actuallyAllowed);
        }

        return $mimeType;
    }

    /**
     * Ensure that the file's extension is allowed.
     * @param  string $extension
     * @param  bool $toLower
     * @return string
     * @throws FileNotSupportedException If the file extension is not allowed
     */
    private function verifyExtension(string $extension, bool $toLower = true): string
    {
        $extensionLower = strtolower($extension);
        $allowed = $this->config->allowedExtensions;
        $forbidden = $this->config->forbiddenExtensions;
        $actuallyAllowed = array_diff($allowed, $forbidden);
        if (!empty($allowed) && !in_array($extensionLower, $actuallyAllowed)) {
            throw FileNotSupportedException::extensionRestricted($extensionLower, $actuallyAllowed);
        }
        if (empty($allowed) && in_array($extensionLower, $forbidden)) {
            throw FileNotSupportedException::extensionRestricted($extensionLower, $actuallyAllowed);
        }

        return $toLower ? $extensionLower : $extension;
    }

    /**
     * Verify that the file being uploaded is not larger than the maximum.
     * @param  int $size
     * @return int
     * @throws FileSizeException If the file is too large
     */
    private function verifyFileSize(int $size): int
    {
        $max = $this->config->maxUploadSize;
        if ($max > 0 && $size > $max) {
            throw FileSizeException::fileIsTooBig($size, $max);
        }

        return $size;
    }

    private function verifyHashes(): void
    {
        foreach ($this->config->expectedHashes as $algo => $expectedHash) {
            if ($expectedHash === null) {
                return;
            }

            $actualHash = $this->source->hash($algo);
            if ($actualHash !== $expectedHash) {
                throw InvalidHashException::hashMismatch(
                    $algo,
                    $expectedHash,
                    $actualHash
                );
            }
        }
    }

    /**
     * Verify that the intended destination is available and handle any duplications.
     * @param  Media $model
     * @return void
     *
     * @throws FileExistsException
     */
    private function verifyDestination(Media $model): void
    {
        $storage = $this->filesystem->disk($model->disk);

        if ($storage->exists($model->getDiskPath())) {
            $this->handleDuplicate($model);
        }
    }

    /**
     * Decide what to do about duplicated files.
     *
     * @param  Media $model
     * @return Media
     * @throws FileExistsException If directory is not writable or file already exists at the destination and on_duplicate is set to 'error'
     */
    private function handleDuplicate(Media $model): Media
    {
        switch ($this->config->onDuplicate) {
            case OnDuplicateBehaviour::Error:
                throw FileExistsException::fileExists($model->getDiskPath());
            case OnDuplicateBehaviour::Replace:
                $this->deleteExistingMedia($model);
                break;
            case OnDuplicateBehaviour::ReplaceWithVariants:
                $this->deleteExistingMedia($model, true);
                break;
            case OnDuplicateBehaviour::Update:
                $original = $model->newQuery()
                   ->where('disk', $model->disk)
                   ->where('directory', $model->directory)
                   ->where('filename', $model->filename)
                   ->where('extension', $model->extension)
                   ->first();

                if ($original) {
                    $model->{$model->getKeyName()} = $original->getKey();
                    $model->exists = true;
                }
                break;
            case OnDuplicateBehaviour::Increment:
            default:
                $model->filename = $this->generateUniqueFilename($model);
        }
        return $model;
    }

    /**
     * Delete the media that previously existed at a destination.
     * @param  Media $model
     * @param  bool $withVariants
     * @return void
     */
    private function deleteExistingMedia(Media $model, bool $withVariants = false): void
    {
        $original = $model->newQuery()
            ->where('disk', $model->disk)
            ->where('directory', $model->directory)
            ->where('filename', $model->filename)
            ->where('extension', $model->extension)
            ->first();
        if ($original) {
            $models = $withVariants ? $original->getAllVariantsAndSelf() : collect([$original]);
            $models->each(
                function (Media $variant) {
                    $variant->delete();
                    $this->deleteExistingFile($variant);
                }
            );
        }
    }

    /**
     * Delete the file on disk.
     * @param  Media $model
     * @return void
     */
    private function deleteExistingFile(Media $model): void
    {
        $this->filesystem->disk($model->disk)->delete($model->getDiskPath());
    }

    /**
     * Increment model's filename until one is found that doesn't already exist.
     * @param  Media $model
     * @return string
     */
    private function generateUniqueFilename(Media $model): string
    {
        $storage = $this->filesystem->disk($model->disk);
        $counter = 0;
        do {
            $filename = "{$model->filename}";
            if ($counter > 0) {
                $filename .= '-' . $counter;
            }
            $path = "{$model->directory}/{$filename}.{$model->extension}";
            ++$counter;
        } while ($storage->exists($path));

        return $filename;
    }

    /**
     * Generate the model's filename.
     * @return string
     */
    private function generateFilename(): string
    {
        if ($this->config->destinationFilename) {
            return $this->config->destinationFilename;
        }

        if ($this->config->hashFilenameAlgorithm) {
            return $this->source->hash($this->config->hashFilenameAlgorithm);
        }

        $filename = $this->source->filename();

        if ($filename === null) {
            ConfigurationException::cannotInferFilename();
        }

        return File::sanitizeFileName(
            $filename,
            null,
            $this->config->forbiddenExtensions
        );
    }

    private function writeToDisk(Media $model): void
    {
        $this->filesystem->disk($model->disk)
            ->put(
                $model->getDiskPath(),
                $this->source->getStream(),
                $this->getOptions()
            );
    }

    public function getOptions(): array
    {
        $options = $this->config->filesystemOptions;
        if (!isset($options['visibility'])) {
            $options['visibility'] = $this->getVisibility();
        }
        return $options;
    }

    public function sanitizeFile(Media $model): void
    {
        if (empty($this->config->fileSanitizers)) {
            return;
        }
        foreach ($this->config->fileSanitizers as $sanitizerClass) {
            if (!is_a($sanitizerClass, SanitizerInterface::class, true)) {
                throw ConfigurationException::invalidSanitizer($sanitizerClass);
            }
            $sanitizer = app($sanitizerClass);
            if ($sanitizer->isApplicable(
                $model->mime_type,
                $model->extension,
                $model->aggregate_type
            )) {
                $result = $sanitizer->sanitize($this->source->getStream());
                if ($result !== null) {
                    $this->source = new StreamAdapter($result);
                }
            }
        }
        // Update the model's size in case the sanitizer modified the file contents
        $model->size = $this->source->size() ?? $model->size;
    }

    /**
     * @param Media $model
     * @return void
     * @throws Exceptions\ImageManipulationException
     */
    public function manipulateImage(Media $model): void
    {
        if (empty($this->config->imageManipulation)
            || $model->aggregate_type !== Media::TYPE_IMAGE
        ) {
            return;
        }
        $manipulation = $this->config->imageManipulation;
        $this->source = $this->imageManipulator->manipulateUpload(
            $model,
            $this->source,
            $manipulation
        );
    }
}
