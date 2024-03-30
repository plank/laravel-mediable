<?php

namespace Plank\Mediable\Tests\Integration\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
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
    private const EXPECTED_HASH = '3ef5e70366086147c2695325d79a25cc';

    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app['filesystem']->disk('uploads')->put('plank.png', $this->sampleFile());
    }

    public static function adapterProvider()
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
                'path' => $file,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'UploadedFileAdapter' => [
                'adapterClass' => UploadedFileAdapter::class,
                'source' => $uploadedFile,
                'path' => $file,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'LocalPathAdapter' => [
                'adapterClass' => LocalPathAdapter::class,
                'source' => $file,
                'path' => $file,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'RemoteUrlAdapter' => [
                'adapterClass' => RemoteUrlAdapter::class,
                'source' => $url,
                'path' => $url,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'RawContentAdapter' => [
                'adapterClass' => RawContentAdapter::class,
                'source' => $string,
                'path' => null,
                'filename' => null,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'DataUrlAdapter_base64' => [
                'adapterClass' => DataUrlAdapter::class,
                'source' => $base64DataUrl,
                'path' => null,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'DataUrlAdapter_urlencode' => [
                'adapterClass' => DataUrlAdapter::class,
                'source' => $rawDataUrl,
                'path' => null,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'StreamResourceAdapter_Local' => [
                'adapterClass' => StreamResourceAdapter::class,
                'source' => $fileResource,
                'path' => $file,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'StreamAdapter_Local' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $fileStream,
                'path' => $file,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'StreamResourceAdapter_Remote' => [
                'adapterClass' => StreamResourceAdapter::class,
                'source' => $httpResource,
                'path' => $url,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'StreamAdapter_Remote' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $httpStream,
                'path' => $url,
                'filename' => self::EXPECTED_FILENAME,
                'extension' => self::EXPECTED_EXTENSION,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'StreamResourceAdapter_Memory' => [
                'adapterClass' => StreamResourceAdapter::class,
                'source' => $memoryResource,
                'path' => null,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'StreamAdapter_Memory' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $memoryStream,
                'path' => null,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => null,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
            'StreamAdapter_Data' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $dataStream,
                'path' => null,
                'filename' => null,
                'extension' => null,
                'inferredMime' => self::EXPECTED_MIME,
                'clientMime' => self::EXPECTED_MIME,
                'isRemote' => false,
                'size' => self::EXPECTED_SIZE,
                'hash' => self::EXPECTED_HASH,
            ],
        ];
        return $data;
    }

    public static function invalidAdapterProvider()
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
            [new FileAdapter(new File($file, false))],
            [new LocalPathAdapter($file)],
            [new RemoteUrlAdapter($url)],
            [new RemoteUrlAdapter('http://example.invalid')],
            [new UploadedFileAdapter($uploadedFile)],
            [new StreamResourceAdapter(fopen(TestCase::sampleFilePath(), 'a'))],
            [new StreamResourceAdapter(fopen('php://stdin', 'w'))],
        ];
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_extracts_expected_information_from_source(
        string $adapterClass,
        mixed $source,
        ?string $path,
        ?string $filename,
        ?string $extension,
        ?string $inferredMime,
        ?string $clientMime,
        ?bool $isRemote,
        ?int $size,
        ?string $hash
    ) {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);
        $this->assertTrue($adapter->valid());

        $stream = $adapter->getStream();
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertTrue($stream->isReadable());

        $this->assertSame($path, $adapter->path());
        $this->assertSame($filename, $adapter->filename());
        $this->assertSame($extension, $adapter->extension());
        $this->assertSame($inferredMime, $adapter->mimeType());
        $this->assertSame($clientMime, $adapter->clientMimeType());
        $this->assertSame($size, $adapter->size());
        $this->assertSame($hash, $adapter->hash());
    }

    /**
     * @dataProvider invalidAdapterProvider
     */
    public function test_it_verifies_file_validity_failure(
        SourceAdapterInterface $adapter
    ) {
        $this->assertFalse($adapter->valid());
    }
}
