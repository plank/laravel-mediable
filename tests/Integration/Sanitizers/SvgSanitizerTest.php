<?php

namespace Plank\Mediable\Tests\Integration\Sanitizers;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\FileSanitizers\SvgSanitizer;
use Plank\Mediable\Media;
use Plank\Mediable\Tests\TestCase;

class SvgSanitizerTest extends TestCase
{
    public function test_it_applies_to_svg_files(): void
    {
        $sanitizer = new SvgSanitizer();
        $this->assertTrue($sanitizer->isApplicable('image/svg+xml', 'svg', Media::TYPE_IMAGE_VECTOR));
        $this->assertFalse($sanitizer->isApplicable('image/jpeg', 'jpg', Media::TYPE_IMAGE));
    }

    public function test_it_sanitizes_svg_files()
    {
        $source = Utils::streamFor(file_get_contents($this->insecureSvgPath()));

        $sanitizer = new SvgSanitizer();
        $result = $sanitizer->sanitize($source);
        $sanitizedContents = $result->getContents();
        $expectedContents = file_get_contents($this->cleanedSvgPath());
        $this->assertSame($expectedContents, $sanitizedContents);
    }
}
