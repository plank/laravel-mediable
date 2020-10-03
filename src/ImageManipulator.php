<?php

namespace Plank\Mediable;

use Illuminate\Filesystem\FilesystemManager;
use Intervention\Image\ImageManager;
use Plank\Mediable\Exceptions\ImageManipulationException;
use Psr\Http\Message\StreamInterface;

class ImageManipulator
{
    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var ImageManipulation[]
     */
    private $variantManipulations = [];

    /**
     * @var FilesystemManager
     */
    private $filesystem;

    public function __construct(ImageManager $imageManager, FilesystemManager $filesystem)
    {
        $this->imageManager = $imageManager;
        $this->filesystem = $filesystem;
    }

    public function addVariantManipulation(
        string $variantName,
        ImageManipulation $manipulation
    ) {
        $this->variantManipulations[$variantName] = $manipulation;
    }

    /**
     * @param ImageManipulation $manipulation
     * @param Media $media
     * @return StreamInterface
     * @throws ImageManipulationException
     */
    public function createVariant(string $variantName, Media $media): Media
    {
        if ($media->aggregate_type != Media::TYPE_IMAGE) {
            throw ImageManipulationException::invalidMediaType($media->aggregate_type);
        }

        $manipulation = $this->getVariantManipulation($variantName);

        $outputFormat = $this->determineOutputFormat($manipulation, $media);
        $image = $this->imageManager->make($media->stream());

        $callback = $manipulation->getCallback();
        $callback($image);

        $outputStream = $image->stream(
            $outputFormat,
            $manipulation->getOutputQuality()
        );

        $modelClass = config('mediable.model');
        /** @var Media $newMedia */
        $newMedia = new $modelClass();
        $newMedia->disk = $media->disk;
        $newMedia->directory = $media->directory;
        $newMedia->filename = sprintf('%s-%s', $media->filename, $variantName);
        $newMedia->extension = $outputFormat;
        $newMedia->mime_type = $this->getMimeTypeForOutputFormat($outputFormat);
        $newMedia->aggregate_type = Media::TYPE_IMAGE;
        $newMedia->size = $outputStream->getSize();

        if ($beforeSave = $manipulation->getBeforeSave()) {
            $beforeSave($newMedia);
        }

        $this->filesystem->disk($newMedia->disk)
            ->writeStream($newMedia->getDiskPath(), $outputStream->detach());

        $newMedia->save();

        return $newMedia;
    }

    /**
     * @param string $variantName
     * @return ImageManipulation
     * @throws ImageManipulationException
     */
    private function getVariantManipulation(string $variantName): ImageManipulation
    {
        if (isset($this->variantManipulations[$variantName])) {
            return $this->variantManipulations[$variantName];
        }

        throw ImageManipulationException::unknownVariant($variantName);
    }

    private function getMimeTypeForOutputFormat(string $outputFormat): string
    {
        return ImageManipulation::MIME_TYPE_MAP[$outputFormat];
    }

    /**
     * @param ImageManipulation $manipulation
     * @param Media $media
     * @return string
     * @throws ImageManipulationException
     */
    private function determineOutputFormat(
        ImageManipulation $manipulation,
        Media $media
    ): string {
        if ($format = $manipulation->getOutputFormat()) {
            return $format;
        }

        // attempt to infer the format from the mime type
        $mime = strtolower($media->mime_type);
        $format = array_search($mime, ImageManipulation::MIME_TYPE_MAP);
        if ($format !== false) {
            return $format;
        }

        // attempt to infer the format from the file extension
        $extension = strtolower($media->extension);
        if (in_array($extension, ImageManipulation::VALID_IMAGE_FORMATS)) {
            return $extension;
        }
        if ($extension === 'jpeg') {
            return ImageManipulation::FORMAT_JPG;
        }
        if ($extension === 'tiff') {
            return ImageManipulation::FORMAT_TIFF;
        }

        throw ImageManipulationException::unknownOutputFormat();
    }
}
