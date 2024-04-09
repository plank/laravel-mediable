<?php

namespace Plank\Mediable;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Collection;
use Intervention\Image\Commands\StreamCommand;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Plank\Mediable\Exceptions\ImageManipulationException;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\SourceAdapters\SourceAdapterInterface;
use Plank\Mediable\SourceAdapters\StreamAdapter;
use Psr\Http\Message\StreamInterface;
use Spatie\ImageOptimizer\OptimizerChain;

class ImageManipulator
{
    private ?ImageManager $imageManager;

    /**
     * @var ImageManipulation[]
     */
    private array $variantDefinitions = [];

    private array $variantDefinitionGroups = [];

    /**
     * @var FilesystemManager
     */
    private $filesystem;

    private ImageOptimizer $imageOptimizer;

    public function __construct(
        ?ImageManager $imageManager,
        FilesystemManager $filesystem,
        ImageOptimizer $imageOptimizer
    ) {
        $this->imageManager = $imageManager;
        $this->filesystem = $filesystem;
        $this->imageOptimizer = $imageOptimizer;
    }

    public function defineVariant(
        string $variantName,
        ImageManipulation $manipulation,
        ?array $tags = []
    ) {
        if (!$this->imageManager) {
            throw ConfigurationException::interventionImageNotConfigured();
        }
        $this->variantDefinitions[$variantName] = $manipulation;
        foreach ($tags as $tag) {
            $this->variantDefinitionGroups[$tag][] = $variantName;
        }
    }

    public function hasVariantDefinition(string $variantName): bool
    {
        return isset($this->variantDefinitions[$variantName]);
    }

    /**
     * @param string $variantName
     * @return ImageManipulation
     * @throws ImageManipulationException if Variant is not defined
     */
    public function getVariantDefinition(string $variantName): ImageManipulation
    {
        if (isset($this->variantDefinitions[$variantName])) {
            return $this->variantDefinitions[$variantName];
        }

        throw ImageManipulationException::unknownVariant($variantName);
    }

    public function getAllVariantDefinitions(): Collection
    {
        return collect($this->variantDefinitions);
    }

    public function getAllVariantNames(): array
    {
        return array_keys($this->variantDefinitions);
    }

    public function getVariantDefinitionsByTag(string $tag): Collection
    {
        return $this->getAllVariantDefinitions()
            ->intersectByKeys(array_flip($this->getVariantNamesByTag($tag)));
    }

    public function getVariantNamesByTag(string $tag): array
    {
        return $this->variantDefinitionGroups[$tag] ?? [];
    }

    /**
     * @param Media $media
     * @param string $variantName
     * @param bool $forceRecreate
     * @return Media
     * @throws ImageManipulationException
     */
    public function createImageVariant(
        Media $media,
        string $variantName,
        bool $forceRecreate = false
    ): Media {
        if (!$this->imageManager) {
            throw ConfigurationException::interventionImageNotConfigured();
        }

        $this->validateMedia($media);

        $modelClass = config('mediable.model');
        /** @var Media $variant */
        $variant = new $modelClass();
        $recreating = false;
        $originalVariant = null;

        // don't recreate if that variant already exists for the model
        if ($media->hasVariant($variantName)) {
            $variant = $media->findVariant($variantName);
            if ($forceRecreate) {
                // replace the existing variant
                $recreating = true;
                $originalVariant = clone $variant;
            } else {
                // variant already exists, nothing more to do
                return $variant;
            }
        }

        $manipulation = $this->getVariantDefinition($variantName);

        $outputFormat = $this->determineOutputFormat($manipulation, $media);
        if (method_exists($this->imageManager, 'read')) {
            // Intervention Image  >=3.0
            $image = $this->imageManager->read($media->contents());
        } else {
            // Intervention Image <3.0
            $image = $this->imageManager->make($media->contents());
        }

        $callback = $manipulation->getCallback();
        $callback($image, $media);

        $outputStream = $this->imageToStream(
            $image,
            $outputFormat,
            $manipulation->getOutputQuality()
        );

        if ($manipulation->shouldOptimize()) {
            $outputStream = $this->imageOptimizer->optimizeImage(
                $outputStream,
                $manipulation->getOptimizerChain()
            );
        }

        $variant->variant_name = $variantName;
        $variant->original_media_id = $media->isOriginal()
            ? $media->getKey()
            : $media->original_media_id; // attach variants of variants to the same original

        $variant->disk = $manipulation->getDisk() ?? $media->disk;
        $variant->directory = $manipulation->getDirectory() ?? $media->directory;
        $variant->filename = $this->determineFilename(
            $media->findOriginal(),
            $manipulation,
            $variant,
            $outputStream
        );
        $variant->extension = $outputFormat;
        $variant->mime_type = $this->getMimeTypeForOutputFormat($outputFormat);
        $variant->aggregate_type = Media::TYPE_IMAGE;
        $variant->size = $outputStream->getSize();

        $this->checkForDuplicates($variant, $manipulation, $originalVariant);
        if ($beforeSave = $manipulation->getBeforeSave()) {
            $beforeSave($variant, $originalVariant);
            // destination may have been changed, check for duplicates again
            $this->checkForDuplicates($variant, $manipulation, $originalVariant);
        }

        if ($recreating) {
            // delete the original file for that variant
            $this->filesystem->disk($originalVariant->disk)
                ->delete($originalVariant->getDiskPath());
        }

        $visibility = $manipulation->getVisibility();
        if ($visibility == 'match') {
            $visibility = ($media->isVisible() ? 'public' : 'private');
        }
        $options = [];
        if ($visibility) {
            $options = ['visibility' => $visibility];
        }

        $this->filesystem->disk($variant->disk)
            ->writeStream(
                $variant->getDiskPath(),
                $outputStream->detach(),
                $options
            );

        $variant->save();

        return $variant;
    }

    /**
     * @param Media $media
     * @param SourceAdapterInterface $source
     * @param ImageManipulation $manipulation
     * @return StreamAdapter
     * @throws ImageManipulationException
     */
    public function manipulateUpload(
        Media $media,
        SourceAdapterInterface $source,
        ImageManipulation $manipulation
    ): StreamAdapter {
        if (!$this->imageManager) {
            throw ConfigurationException::interventionImageNotConfigured();
        }

        $outputFormat = $this->determineOutputFormat($manipulation, $media);
        if (method_exists($this->imageManager, 'read')) {
            // Intervention Image  >=3.0
            $image = $this->imageManager->read($source->getStream()->getContents());
        } else {
            // Intervention Image <3.0
            $image = $this->imageManager->make($source->getStream()->getContents());
        }

        $callback = $manipulation->getCallback();
        $callback($image, $media);

        $outputStream = $this->imageToStream(
            $image,
            $outputFormat,
            $manipulation->getOutputQuality()
        );

        if ($manipulation->shouldOptimize()) {
            $outputStream = $this->imageOptimizer->optimizeImage(
                $outputStream,
                $manipulation->getOptimizerChain()
            );
        }

        $media->extension = $outputFormat;
        $media->mime_type = $this->getMimeTypeForOutputFormat($outputFormat);
        $media->aggregate_type = Media::TYPE_IMAGE;
        $media->size = $outputStream->getSize();

        return new StreamAdapter($outputStream);
    }

    private function getMimeTypeForOutputFormat(string $outputFormat): string
    {
        return ImageManipulation::MIME_TYPE_MAP[$outputFormat];
    }

    /**
     * @param ImageManipulation $manipulation
     * @param Media $media
     * @return string
     * @throws ImageManipulationException If output format cannot be determined
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

    public function determineFilename(
        Media $originalMedia,
        ImageManipulation $manipulation,
        Media $variant,
        StreamInterface $stream
    ): string {
        if ($filename = $manipulation->getFilename()) {
            return $filename;
        }

        if ($manipulation->isUsingHashForFilename()) {
            return $this->getHashFromStream(
                $stream,
                $manipulation->getHashFilenameAlgo() ?? 'md5'
            );
        }
        return sprintf('%s-%s', $originalMedia->filename, $variant->variant_name);
    }

    public function validateMedia(Media $media): void
    {
        if ($media->aggregate_type != Media::TYPE_IMAGE) {
            throw ImageManipulationException::invalidMediaType($media->aggregate_type);
        }
    }

    private function getHashFromStream(StreamInterface $stream, string $algo): string
    {
        $stream->rewind();
        $hash = hash_init($algo);
        while ($chunk = $stream->read(2048)) {
            hash_update($hash, $chunk);
        }
        $filename = hash_final($hash);
        $stream->rewind();

        return $filename;
    }

    private function checkForDuplicates(
        Media $variant,
        ImageManipulation $manipulation,
        Media $originalVariant = null
    ) {
        if ($originalVariant
            && $variant->disk === $originalVariant->disk
            && $variant->getDiskPath() === $originalVariant->getDiskPath()
        ) {
            // same as the original, no conflict as we are going to replace the file anyways
            return;
        }

        if (!$this->filesystem->disk($variant->disk)->exists($variant->getDiskPath())) {
            // no conflict, carry on
            return;
        }

        switch ($manipulation->getOnDuplicateBehaviour()) {
            case ImageManipulation::ON_DUPLICATE_ERROR:
                throw ImageManipulationException::fileExists($variant->getDiskPath());

            case ImageManipulation::ON_DUPLICATE_INCREMENT:
            default:
                $variant->filename = $this->generateUniqueFilename($variant);
                break;
        }
    }

    /**
     * Increment model's filename until one is found that doesn't already exist.
     * @param Media $model
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

    private function imageToStream(
        Image $image,
        string $outputFormat,
        int $outputQuality
    ) {
        if (class_exists(StreamCommand::class)) {
            // Intervention Image  <3.0
            return $image->stream(
                $outputFormat,
                $outputQuality
            );
        }

        $formatted = match ($outputFormat) {
            ImageManipulation::FORMAT_JPG => $image->toJpeg($outputQuality),
            ImageManipulation::FORMAT_PNG => $image->toPng(),
            ImageManipulation::FORMAT_GIF => $image->toGif(),
            ImageManipulation::FORMAT_WEBP => $image->toBitmap(),
            ImageManipulation::FORMAT_TIFF => $image->toTiff($outputQuality),
            default => throw ImageManipulationException::unknownOutputFormat(),
        };
        return Utils::streamFor($formatted->toFilePointer());
    }
}
