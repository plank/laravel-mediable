<?php

namespace Plank\Mediable;

use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\Helpers\File;
use Spatie\ImageOptimizer\Optimizer;
use Spatie\ImageOptimizer\OptimizerChain;

class ImageManipulation
{
    public const FORMAT_JPG = 'jpg';
    public const FORMAT_PNG = 'png';
    public const FORMAT_GIF = 'gif';
    public const FORMAT_TIFF = 'tif';
    public const FORMAT_BMP = 'bmp';
    public const FORMAT_WEBP = 'webp';

    public const VALID_IMAGE_FORMATS = [
        self::FORMAT_JPG,
        self::FORMAT_PNG,
        self::FORMAT_GIF,
        self::FORMAT_TIFF,
        self::FORMAT_BMP
    ];

    public const MIME_TYPE_MAP = [
        self::FORMAT_JPG => 'image/jpeg',
        self::FORMAT_PNG => 'image/png',
        self::FORMAT_GIF => 'image/gif',
        self::FORMAT_TIFF => 'image/tiff',
        self::FORMAT_BMP => 'image/bmp',
        self::FORMAT_WEBP => 'image/webp'
    ];

    public const ON_DUPLICATE_INCREMENT = 'increment';
    public const ON_DUPLICATE_ERROR = 'error';

    /** @var callable */
    private $callback;

    private ?string $outputFormat = null;

    private int $outputQuality = 90;

    private ?string $disk = null;

    private ?string $directory = null;

    private ?string $filename = null;

    private ?string $hashFilenameAlgo = null;

    private string $onDuplicateBehaviour = self::ON_DUPLICATE_INCREMENT;

    /** @var string|null */
    private ?string $visibility = null;

    /** @var callable|null */
    private $beforeSave;

    private bool $shouldOptimize;

    /** @var array<class-string<Optimizer>,string[]> */
    private array $optimizers;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
        $this->shouldOptimize = config('mediable.image_optimization.enabled', true);
        $this->setOptimizers(config('mediable.image_optimization.optimizers', []));
    }

    public static function make(callable $callback): self
    {
        return new self($callback);
    }

    /**
     * @return callable
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }

    /**
     * @return int
     */
    public function getOutputQuality(): int
    {
        return $this->outputQuality;
    }

    /**
     * @param int $outputQuality
     * @return $this
     */
    public function setOutputQuality(int $outputQuality): self
    {
        $this->outputQuality = min(100, max(0, $outputQuality));

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOutputFormat(): ?string
    {
        return $this->outputFormat;
    }

    /**
     * @param string|null $outputFormat
     * @return $this
     */
    public function setOutputFormat(?string $outputFormat): self
    {
        $this->outputFormat = $outputFormat;

        return $this;
    }

    /**
     * @return $this
     */
    public function outputJpegFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_JPG);

        return $this;
    }

    /**
     * @return $this
     */
    public function outputPngFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_PNG);

        return $this;
    }

    /**
     * @return $this
     */
    public function outputGifFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_GIF);

        return $this;
    }

    /**
     * @return $this
     */
    public function outputTiffFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_TIFF);

        return $this;
    }

    /**
     * @return $this
     */
    public function outputBmpFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_BMP);

        return $this;
    }

    /**
     * @return $this
     */
    public function outputWebpFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_WEBP);

        return $this;
    }

    /**
     * @return callable
     */
    public function getBeforeSave(): ?callable
    {
        return $this->beforeSave;
    }

    /**
     * Set the filesystem disk and relative directory where the file will be saved.
     *
     * @param  string $disk
     * @param  string $directory
     *
     * @return $this
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
     */
    public function toDisk(string $disk): self
    {
        if (!array_key_exists($disk, config('filesystems.disks', []))) {
            throw ConfigurationException::diskNotFound($disk);
        }
        $this->disk = $disk;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDisk(): ?string
    {
        return $this->disk;
    }

    /**
     * Set the directory relative to the filesystem disk at which the file will be saved.
     * @param string $directory
     * @return $this
     */
    public function toDirectory(string $directory): self
    {
        $this->directory = File::sanitizePath($directory);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDirectory(): ?string
    {
        return $this->directory;
    }

    /**
     * Specify the filename to copy to the file to.
     * @param string $filename
     * @return $this
     */
    public function useFilename(string $filename): self
    {
        $this->filename = File::sanitizeFilename($filename);
        $this->hashFilenameAlgo = null;

        return $this;
    }

    /**
     * Indicates to the uploader to generate a filename using the file's MD5 hash.
     * @return $this
     */
    public function useHashForFilename(string $algo = 'md5'): self
    {
        $this->hashFilenameAlgo = $algo;
        $this->filename = null;

        return $this;
    }

    /**
     * Restore the default behaviour of using the source file's filename.
     * @return $this
     */
    public function useOriginalFilename(): self
    {
        $this->filename = null;
        $this->hashFilenameAlgo = null;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function isUsingHashForFilename(): bool
    {
        return $this->hashFilenameAlgo !== null;
    }

    public function getHashFilenameAlgo(): ?string
    {
        return $this->hashFilenameAlgo;
    }

    /**
     * @return $this
     */
    public function onDuplicateIncrement(): self
    {
        $this->onDuplicateBehaviour = self::ON_DUPLICATE_INCREMENT;
        return $this;
    }

    /**
     * @return $this
     */
    public function onDuplicateError(): self
    {
        $this->onDuplicateBehaviour = self::ON_DUPLICATE_ERROR;
        return $this;
    }

    /**
     * @return string
     */
    public function getOnDuplicateBehaviour(): string
    {
        return $this->onDuplicateBehaviour;
    }

    public function makePrivate(): self
    {
        $this->visibility = 'private';
        return $this;
    }

    public function makePublic(): self
    {
        $this->visibility = 'public';
        return $this;
    }

    public function matchOriginalVisibility(): self
    {
        $this->visibility = 'match';
        return $this;
    }

    public function setVisibility(?string $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    /**
     * @param callable $beforeSave
     * @return $this
     */
    public function beforeSave(callable $beforeSave): self
    {
        $this->beforeSave = $beforeSave;

        return $this;
    }

    /**
     * Disable image optimization.
     * @return $this
     */
    public function noOptimization(): self
    {
        $this->shouldOptimize = false;

        return $this;
    }

    /**
     * Enable image optimization.
     * @param array<class-string<Optimizer>,string[]> $customOptimizers Override default optimizers.
     *     The array keys should be the fully qualified class names of the optimizers to use.
     *     The array values should be arrays of command line arguments to pass to the optimizer.
     *     DO NOT PASS UNTRUSTED USER INPUT AS COMMAND LINE ARGUMENTS
     * @return $this
     * @throws ConfigurationException
     */
    public function optimize(?array $customOptimizers = null): self
    {
        if ($customOptimizers !== null) {
            $this->setOptimizers($customOptimizers);
        }
        $this->shouldOptimize = true;

        return $this;
    }

    public function shouldOptimize(): bool
    {
        return $this->shouldOptimize && !empty($this->optimizers);
    }

    public function getOptimizerChain(): OptimizerChain
    {
        $chain = new OptimizerChain();
        foreach ($this->optimizers as $optimizerClass => $args) {
            $optimizer = new $optimizerClass($args);
            $chain->addOptimizer($optimizer);
        }
        return $chain;
    }

    private function setOptimizers(array $customOptimizers): void
    {
        foreach ($customOptimizers as $optimizerClass => $args) {
            if (!is_a($optimizerClass, Optimizer::class, true)) {
                throw ConfigurationException::invalidOptimizer($optimizerClass);
            }
        }
        $this->optimizers = $customOptimizers;
    }
}
