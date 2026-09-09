<?php

namespace Plank\Mediable;

use Plank\Mediable\Enum\OnDuplicateBehaviour;
use Plank\Mediable\FileSanitizers\SanitizerInterface;

class MediaUploaderConfiguration
{
    /**
     * @var array<string, array{mime_types:array<string>, extensions:array<string>}>
     */
    public array $definedAggregateTypes;

    /**
     * @var list<string>
     */
    public array $allowedAggregateTypes;

    public bool $allowUnrecognizedTypes;

    public bool $strictTypeChecking;
    public array $allowedExtensions;
    public array $forbiddenExtensions;

    public array $allowedMimeTypes;
    public array $forbiddenMimeTypes;
    public bool $preferClientMimeType;
    public int $maxUploadSize;

    /**
     * File hashes to validate the uploaded file against.
     * The key is the hash algorithm (e.g. "md5", "sha256") and the value is the expected hash value.
     * @var array<string, string>
     */
    public array $expectedHashes = [];

    /**
     * @var list<string>
     */
    public array $allowedDisks;
    public ?string $destinationDisk;
    public string $defaultDisk;

    /**
     * Path relative to the filesystem disk root where the file should be stored. Can include subdirectories, e.g. "images/uploads"
     */
    public string $destinationDirectory;

    /**
     * Filename to use for the stored file.
     * @var string|null
     */
    public ?string $destinationFilename = null;

    /**
     * Hash algorithm to use for generating the filename.
     * If set, the filename will be generated as a hash of the file contents using the specified algorithm (e.g. "md5", "sha256").
     * If null, the original filename or the value of $destinationFilename will be used.
     * @var string|null
     */
    public ?string $hashFilenameAlgorithm = null;

    /**
     * Alternative text to associate with the media file, if applicable (e.g. for images).
     * This can be used for accessibility purposes.
     * @var string|null
     */
    public ?string $fileAlternativeText = null;

    /**
     * Filesystem visibility to set for the stored file (e.g. "public" or "private").
     * If null, the default visibility of the filesystem will be used.
     * @var string|null
     */
    public ?string $fileVisibility = null;

    /**
     * Additional options to pass to the filesystem when storing the file.
     * @var array
     */
    public array $filesystemOptions = [];

    /**
     * List of file sanitizer classes to apply to the uploaded file before saving.
     * @var list<class-string<SanitizerInterface>>
     */
    public array $fileSanitizers;
    public ?ImageManipulation $imageManipulation = null;

    /**
     * A callback that will be called before saving the media record to the database.
     * @var \Closure|null
     */
    public ?\Closure $beforeSaveCallback = null;

    /**
     * @var class-string<Media>
     */
    public string $modelClass;

    public OnDuplicateBehaviour $onDuplicate;

    public function __construct(array $config)
    {
        $this->definedAggregateTypes = $config['aggregate_types'] ?? [];
        $this->allowedAggregateTypes = $config['allowed_aggregate_types'] ?? [];
        $this->allowUnrecognizedTypes = $config['allow_unrecognized_types'] ?? false;
        $this->strictTypeChecking = $config['strict_type_checking'] ?? false;

        $this->maxUploadSize = $config['max_size'] ?? 0;
        $this->allowedExtensions = $config['allowed_extensions'] ?? [];
        $this->forbiddenExtensions = $config['forbidden_extensions'] ?? [];
        $this->allowedMimeTypes = $config['allowed_mime_types'] ?? [];
        $this->forbiddenMimeTypes = $config['forbidden_mime_types'] ?? [];
        $this->preferClientMimeType = $config['prefer_client_mime_type'] ?? false;

        $this->allowedDisks = $config['allowed_disks'] ?? [];
        $this->destinationDisk = $config['destination_disk'] ?? null;
        $this->defaultDisk = $config['default_disk'] ?? '';
        $this->destinationDirectory = $config['destination_directory'] ?? '';
        $this->destinationFilename = $config['destination_filename'] ?? null;
        $this->hashFilenameAlgorithm = $config['hash_filename_algorithm'] ?? null;

        $this->modelClass = $config['model'] ?? Media::class;
        $this->fileSanitizers = $config['file_sanitizers'] ?? [];
        $this->imageManipulation = null;
        $this->onDuplicate = OnDuplicateBehaviour::tryFrom($config['on_duplicate'] ?? 'increment')
            ?? OnDuplicateBehaviour::Increment;
    }

    public static function fromConfig(): self
    {
        return new self(config('mediable', []));
    }
}
