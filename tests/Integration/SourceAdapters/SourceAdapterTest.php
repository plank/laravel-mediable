<?php

namespace Plank\Mediable\Tests\Integration\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\SourceAdapters\DataUrlAdapter;
use Plank\Mediable\SourceAdapters\FileAdapter;
use Plank\Mediable\SourceAdapters\LocalPathAdapter;
use Plank\Mediable\SourceAdapters\RawContentAdapter;
use Plank\Mediable\SourceAdapters\RemoteUrlAdapter;
use Plank\Mediable\SourceAdapters\SourceAdapterInterface;
use Plank\Mediable\SourceAdapters\StreamAdapter;
use Plank\Mediable\SourceAdapters\StreamResourceAdapter;
use Plank\Mediable\SourceAdapters\UploadedFileAdapter;
use Plank\Mediable\Tests\TestCase;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SourceAdapterTest extends TestCase
{
    private const EXPECTED_FILENAME = 'plank';
    private const EXPECTED_EXTENSION = 'png';
    private const EXPECTED_MIME = 'image/png';
    private const EXPECTED_SIZE = 7173;
    private const EXPECTED_HASH_MD5 = '3ef5e70366086147c2695325d79a25cc';
    private const EXPECTED_HASH_SHA1 = '5e96e1fa58067853219c4cb6d3c1ce01cc5cc8ce';

    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app['filesystem']->disk('uploads')->put('plank.png', $this->sampleFile());
    }

    public static function adapterProvider(): array
    {
        $file = TestCase::sampleFilePath();
        $string = file_get_contents($file);
        $base64DataUrl = 'data:image/png;base64,' . base64_encode($string);
        $rawDataUrl = 'data:image/png,' . rawurlencode($string);
        $url = TestCase::remoteFilePath() . '?foo=bar.baz';

        $uploadedFile = new UploadedFile(
            $file,
            'plank.png',
            'image/png',
            UPLOAD_ERR_OK,
            true
        );

        $fileResource = fopen($file, 'rb');
        $fileStream = Utils::streamFor(fopen($file, 'rb'));

        $httpResource = fopen($url, 'rb');
        $httpStream = Utils::streamFor(fopen($url, 'rb'));

        $memoryResource = fopen('php://memory', 'w+b');
        fwrite($memoryResource, $string);
        rewind($memoryResource);

        $memoryStream = Utils::streamFor(fopen('php://memory', 'w+b'));
        $memoryStream->write($string);

        $dataStream = Utils::streamFor(fopen('data://image/png,' . rawurlencode($string), 'rb'));
        $data = [
            'FileAdapter' => [
                'adapterClass' => FileAdapter::class,
                'source' => new File($file),
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'UploadedFileAdapter' => [
                'adapterClass' => UploadedFileAdapter::class,
                'source' => $uploadedFile,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'LocalPathAdapter' => [
                'adapterClass' => LocalPathAdapter::class,
                'source' => $file,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'RemoteUrlAdapter' => [
                'adapterClass' => RemoteUrlAdapter::class,
                'source' => $url,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'RawContentAdapter' => [
                'adapterClass' => RawContentAdapter::class,
                'source' => $string,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'DataUrlAdapter_base64' => [
                'adapterClass' => DataUrlAdapter::class,
                'source' => $base64DataUrl,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'DataUrlAdapter_urlencode' => [
                'adapterClass' => DataUrlAdapter::class,
                'source' => $rawDataUrl,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'StreamResourceAdapter_Local' => [
                'adapterClass' => StreamResourceAdapter::class,
                'source' => $fileResource,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'StreamAdapter_Local' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $fileStream,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'StreamResourceAdapter_Remote' => [
                'adapterClass' => StreamResourceAdapter::class,
                'source' => $httpResource,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'StreamAdapter_Remote' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $httpStream,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'StreamResourceAdapter_Memory' => [
                'adapterClass' => StreamResourceAdapter::class,
                'source' => $memoryResource,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'StreamAdapter_Memory' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $memoryStream,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
            'StreamAdapter_Data' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $dataStream,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'size' => self::EXPECTED_SIZE,
                'md5Hash' => self::EXPECTED_HASH_MD5,
                'sha1Hash' => self::EXPECTED_HASH_SHA1,
            ],
        ];
        return $data;
    }

    public static function invalidAdapterProvider(): array
    {
        $file = __DIR__ . '/../../_data/invalid.png';
        $url = 'https://raw.githubusercontent.com/plank/laravel-mediable/master/tests/_data/invalid.png';

        $uploadedFile = new UploadedFile(
            $file,
            'invalid.png',
            'image/png',
            UPLOAD_ERR_CANT_WRITE,
            false
        );

        return [
            [FileAdapter::class, new File($file, false)],
            [LocalPathAdapter::class, $file],
            [RemoteUrlAdapter::class, $url],
            [RemoteUrlAdapter::class, 'http://example.invalid'],
            [UploadedFileAdapter::class, $uploadedFile],
            [StreamResourceAdapter::class, fopen(TestCase::sampleFilePath(), 'a')],
            [StreamResourceAdapter::class, fopen('php://stdin', 'w')],
        ];
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_extracts_expected_information_from_source(
        string $adapterClass,
        mixed $source,
        ?string $filename,
        ?string $extension,
        string $inferredMime,
        ?string $clientMime,
        int $size,
        string $md5Hash,
        string $sha1Hash
    ) {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);

        $stream = $adapter->getStream();
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertTrue($stream->isReadable());
        $this->assertSame($filename, $adapter->filename());
        $this->assertSame($extension, $adapter->extension());
        $this->assertSame($inferredMime, $adapter->mimeType());
        $this->assertSame($clientMime, $adapter->clientMimeType());
        $this->assertSame($size, $adapter->size());
        $this->assertSame($md5Hash, $adapter->hash());
        $this->assertSame($sha1Hash, $adapter->hash('sha1'));
    }

    /**
     * @dataProvider invalidAdapterProvider
     */
    public function test_it_verifies_file_validity_failure(
        string $adapterClass,
        $args
    ) {
        $this->expectException(ConfigurationException::class);
        new $adapterClass($args);
    }
}
