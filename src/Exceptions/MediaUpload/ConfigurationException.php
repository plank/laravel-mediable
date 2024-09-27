<?php
declare(strict_types=1);

namespace Plank\Mediable\Exceptions\MediaUpload;

use Plank\Mediable\Exceptions\MediaUploadException;

class ConfigurationException extends MediaUploadException
{
    public static function cannotSetAdapter(string $class): self
    {
        return new self("Could not set adapter of class `{$class}`. Must implement `\Plank\Mediable\SourceAdapters\SourceAdapterInterface`.");
    }

    public static function cannotSetModel(string $class): self
    {
        return new self("Could not set `{$class}` as Media model class. Must extend `\Plank\Mediable\Media`.");
    }

    public static function noSourceProvided(): self
    {
        return new self('No source provided for upload.');
    }

    public static function unrecognizedSource($source): self
    {
        if (is_object($source)) {
            $source = get_class($source);
        } elseif (is_resource($source)) {
            $source = get_resource_type($source);
        }

        return new self("Could not recognize source, `{$source}` provided.");
    }

    public static function invalidSource(string $message, ?\Throwable $original): self
    {
        return new self("Invalid source provided. {$message}", 0, $original);
    }

    public static function diskNotFound(string $disk): self
    {
        return new self("Cannot find disk named `{$disk}`.");
    }

    public static function cannotInferFilename(): self
    {
        return new self('No filename is provided and cannot infer filename from the provided source.');
    }

    public static function invalidOptimizer(string $optimizerClass): self
    {
        return new self("Invalid optimizer class `{$optimizerClass}`. Must implement `\Spatie\ImageOptimizer\Optimizer`.");
    }

    public static function interventionImageNotConfigured(): self
    {
        return new self("Before variants can be created, the intervention/image package must be configured in the Laravel container.");
    }
}
