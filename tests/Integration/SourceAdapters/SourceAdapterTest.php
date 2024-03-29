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
    private const READABLE_MODES = [
        'r' => true,
        'w+' => true,
        'r+' => true,
        'x+' => true,
        'c+' => true,
        'rb' => true,
        'w+b' => true,
        'r+b' => true,
        'x+b' => true,
        'c+b' => true,
        'rt' => true,
        'w+t' => true,
        'r+t' => true,
        'x+t' => true,
        'c+t' => true,
        'a+' => true
    ];

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
        $mime = 'image/png';
        $data = [
            'FileAdapter' => [
                'adapterClass' => FileAdapter::class,
                'source' => new File($file),
                'path' => $file,
                'filename' => 'plank',
                'inferredMime' => $mime,
                'clientMime' => null
            ],
            'UploadedFileAdapter' => [
                'adapterClass' => UploadedFileAdapter::class,
                'source' => $uploadedFile,
                'path' => $file,
                'filename' => 'plank',
                'inferredMime' => $mime,
                'clientMime' => $mime
            ],
            'LocalPathAdapter' => [
                'adapterClass' => LocalPathAdapter::class,
                'source' => $file,
                'path' => $file,
                'filename' => 'plank',
                'inferredMime' => $mime,
                'clientMime' => null
            ],
            'RemoteUrlAdapter' => [
                'adapterClass' => RemoteUrlAdapter::class,
                'source' => $url,
                'path' => $url,
                'filename' => 'plank',
                'inferredMime' => null,
                'clientMime' => $mime
            ],
            'RawContentAdapter' => [
                'adapterClass' => RawContentAdapter::class,
                'source' => $string,
                'path' => null,
                'filename' => '',
                'inferredMime' => $mime,
                'clientMime' => null
            ],
            'DataUrlAdapter_base64' => [
                'adapterClass' => DataUrlAdapter::class,
                'source' => $base64DataUrl,
                'path' => null,
                'filename' => '',
                'inferredMime' => $mime,
                'clientMime' => $mime
            ],
            'DataUrlAdapter_urlencode' => [
                'adapterClass' => DataUrlAdapter::class,
                'source' => $rawDataUrl,
                'path' => null,
                'filename' => '',
                'inferredMime' => $mime,
                'clientMime' => $mime
            ],
            'StreamResourceAdapter_Local' => [
                'adapterClass' => StreamResourceAdapter::class,
                'source' => $fileResource,
                'path' => $file,
                'filename' => 'plank',
                'inferredMime' => $mime,
                'clientMime' => null
            ],
            'StreamAdapter_Local' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $fileStream,
                'path' => $file,
                'filename' => 'plank',
                'inferredMime' => $mime,
                'clientMime' => null
            ],
            'StreamResourceAdapter_Remote' => [
                'adapterClass' => StreamResourceAdapter::class,
                'source' => $httpResource,
                'path' => $url,
                'filename' => 'plank',
                'inferredMime' => null,
                'clientMime' => $mime
            ],
            'StreamAdapter_Remote' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $httpStream,
                'path' => $url,
                'filename' => 'plank',
                'inferredMime' => null,
                'clientMime' => $mime
            ],
            'StreamResourceAdapter_Memory' => [
                'adapterClass' => StreamResourceAdapter::class,
                'source' => $memoryResource,
                'path' => '',
                'filename' => '',
                'inferredMime' => $mime,
                'clientMime' => null
            ],
            'StreamAdapter_Memory' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $memoryStream,
                'path' => '',
                'filename' => '',
                'inferredMime' => $mime,
                'clientMime' => null
            ],
            'StreamAdapter_Data' => [
                'adapterClass' => StreamAdapter::class,
                'source' => $dataStream,
                'path' => '',
                'filename' => '',
                'inferredMime' => $mime,
                'clientMime' => $mime
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
    public function test_it_can_return_source($adapterClass, $source)
    {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);
        $this->assertEquals($source, $adapter->getSource());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_absolute_path($adapterClass, $source, $path)
    {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);
        $this->assertEquals($path, $adapter->path());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_filename($adapterClass, $source, $path, $filename)
    {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);
        $this->assertSame($filename, $adapter->filename());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_extension($adapterClass, $source)
    {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);
        $this->assertEquals('png', $adapter->extension());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_mime_type($adapterClass, $source, $path, $filename, $inferredMime, $clientMime)
    {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);
        $this->assertEquals($inferredMime, $adapter->mimeType());
        $this->assertEquals($clientMime, $adapter->clientMimeType());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_file_contents($adapterClass, $source)
    {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);
        $this->assertIsString($adapter->contents());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_to_stream(
        $adapterClass,
        $source
    ) {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);
        $stream = $adapter->getStream();

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertTrue($stream->isReadable());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_file_size($adapterClass, $source)
    {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);
        $this->assertEquals(7173, $adapter->size());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_verifies_file_validity($adapterClass, $source)
    {
        /** @var SourceAdapterInterface $adapter */
        $adapter = new $adapterClass($source);
        $this->assertTrue($adapter->valid());
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
