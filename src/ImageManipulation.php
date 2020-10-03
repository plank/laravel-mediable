<?php

namespace Plank\Mediable;

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

    /** @var callable */
    private $callback;

    /** @var string|null */
    private $outputFormat;

    /** @var int */
    private $outputQuality = 90;

    /** @var callable|null */
    private $beforeSave;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public static function make(callable $callback)
    {
        return new self($callback);
    }

    /**
     * @return \Closure
     */
    public function getCallback(): \Closure
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
    public function toJpegFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_JPG);

        return $this;
    }

    /**
     * @return $this
     */
    public function toPngFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_PNG);

        return $this;
    }

    /**
     * @return $this
     */
    public function toGifFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_GIF);

        return $this;
    }

    /**
     * @return $this
     */
    public function toTiffFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_TIFF);

        return $this;
    }

    /**
     * @return $this
     */
    public function toBmpFormat(): self
    {
        $this->setOutputFormat(self::FORMAT_BMP);

        return $this;
    }

    /**
     * @return $this
     */
    public function toWebpFormat(): self
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
     * @param callable $beforeSave
     * @return $this
     */
    public function beforeSave(callable $beforeSave): self
    {
        $this->beforeSave = $beforeSave;

        return $this;
    }
}
