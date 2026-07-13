<?php

namespace Plank\Mediable\FileSanitizers;

use enshrined\svgSanitize\Sanitizer;
use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\Exceptions\MediaUploadException;
use Psr\Http\Message\StreamInterface;

class SvgSanitizer implements SanitizerInterface
{
    public function isApplicable(
        string $mimeType,
        string $extension,
        string $aggregateType
    ): bool {
        return $mimeType === 'image/svg+xml';
    }

    public function sanitize(StreamInterface $source): ?StreamInterface
    {
        $contents = $source->getContents();
        $enshrined = new Sanitizer();
        $result = $enshrined->sanitize($contents);

        if ($result === false) {
            $issues = $enshrined->getXmlIssues();
            throw MediaUploadException::failedToSanitize(
                "failed to parse SVG: " . $issues[0]['message'] ?? 'unknown error'
            );
        }
        if ($result == $contents) {
            // No changes were made to the file, return null to reuse existing source
            return null;
        }

        // Create a new adapter with the sanitized contents
        return Utils::streamFor($result);
    }
}
