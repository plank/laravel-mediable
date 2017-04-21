<?php

use Plank\Mediable\Stream;
use Plank\Mediable\SourceAdapters\FileAdapter;
use Plank\Mediable\SourceAdapters\RawContentAdapter;
use Plank\Mediable\SourceAdapters\StreamAdapter;
use Plank\Mediable\SourceAdapters\StreamResourceAdapter;
use Plank\Mediable\SourceAdapters\UploadedFileAdapter;
use Plank\Mediable\SourceAdapters\LocalPathAdapter;
use Plank\Mediable\SourceAdapters\RemoteUrlAdapter;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Http\Message\StreamInterface;

class SourceAdapterTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app['filesystem']->disk('uploads')->put('plank.png', fopen(__DIR__.'/../../_data/plank.png', 'r'));
    }

    public function adapterProvider()
    {
        $file = realpath(__DIR__.'/../../_data/plank.png');
        $string = file_get_contents($file);
        $url = 'https://www.plankdesign.com/externaluse/plank.png';

        $fileResource = fopen($file, 'rb');
        $fileStream = new Stream(fopen($file, 'rb'));

        $httpResource = fopen($url, 'rb');
        $httpStream = new Stream(fopen($url, 'rb'));

        $memoryResource = fopen('php://memory', 'w+b');
        fwrite($memoryResource, $string);
        rewind($memoryResource);

        $memoryStream = new Stream(fopen('php://memory', 'w+b'));
        $memoryStream->write($string);

        $data = [
            [FileAdapter::class, new File($file), $file, 'plank'],
            [UploadedFileAdapter::class, new UploadedFile($file, 'plank.png', 'image/png', 7173, UPLOAD_ERR_OK, true), $file, 'plank'],
            [LocalPathAdapter::class, $file, $file, 'plank'],
            [RemoteUrlAdapter::class, $url, $url, 'plank'],
            [RawContentAdapter::class, $string, null, null],
            [StreamResourceAdapter::class, $fileResource, $file, 'plank'],
            [StreamAdapter::class, $fileStream, $file, 'plank'],
            [StreamResourceAdapter::class, $httpResource, $url, 'plank'],
            [StreamAdapter::class, $httpStream, $url, 'plank'],
            [StreamResourceAdapter::class, $memoryResource, 'php://memory', 'memory'],
            [StreamAdapter::class, $memoryStream, 'php://memory', 'memory'],
        ];
        return $data;
    }

    public function invalidAdapterProvider()
    {
        $file = __DIR__ . '/../../_data/invalid.png';
        $url = 'https://www.plankdesign.com/externaluse/invalid.png';

        return [
            [new FileAdapter(new File($file, false))],
            [new LocalPathAdapter($file)],
            [new UploadedFileAdapter(new UploadedFile($file, 'invalid.png', 'image/png', 8444, UPLOAD_ERR_CANT_WRITE, false))],
            [new StreamResourceAdapter(fopen(realpath(__DIR__.'/../../_data/plank.png'), 'a'))],
            [new StreamResourceAdapter(fopen('php://stdin', 'w'))],
        ];
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_can_return_source($adapter, $source)
    {
        $adapter = new $adapter($source);
        $this->assertEquals($source, $adapter->getSource());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_absolute_path($adapter, $source, $path)
    {
        $adapter = new $adapter($source);
        $this->assertEquals($path, $adapter->path());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_filename($adapter, $source, $path, $filename)
    {
        $adapter = new $adapter($source);
        $this->assertEquals($filename, $adapter->filename());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_extension($adapter, $source)
    {
        $adapter = new $adapter($source);
        $this->assertEquals('png', $adapter->extension());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_mime_type($adapter, $source)
    {
        $adapter = new $adapter($source);
        $this->assertEquals('image/png', $adapter->mimeType());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_file_contents($adapter, $source)
    {
        $adapter = new $adapter($source);

        $this->assertInternalType('string', $adapter->contents());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_file_size($adapter, $source)
    {
        $adapter = new $adapter($source);
        $this->assertEquals(7173, $adapter->size());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_verifies_file_validity($adapter, $source)
    {
        $adapter = new $adapter($source);
        $this->assertTrue($adapter->valid());
    }

    /**
     * @dataProvider invalidAdapterProvider
     */
    public function test_it_verifies_file_validity_failure($adapter)
    {
        $this->assertFalse($adapter->valid());
    }
}
