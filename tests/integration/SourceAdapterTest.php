<?php

use Frasmage\Mediable\SourceAdapters\FileAdapter;
use Frasmage\Mediable\SourceAdapters\UploadedFileAdapter;
use Frasmage\Mediable\SourceAdapters\LocalPathAdapter;
use Frasmage\Mediable\SourceAdapters\RemoteUrlAdapter;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SourceAdapterTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();
	}

	protected function getEnvironmentSetUp($app)
	{
		parent::getEnvironmentSetUp($app);
		$app['filesystem']->disk('uploads')->put('plank.png', fopen(__DIR__.'/../_data/plank.png','r'));
		
	}

	public function adapterProvider()
	{
		$file = realpath(__DIR__.'/../_data/plank.png');
		$url = 'http://localhost/uploads/plank.png';
		$data = [
			[FileAdapter::class, new File($file), $file],
			[UploadedFileAdapter::class, new UploadedFile($file, 'plank.png', 'image/png', 8444, UPLOAD_ERR_OK, true), $file],
			[LocalPathAdapter::class, $file, $file],
			// [RemoteUrlAdapter::class, $url, $url]
		];
		return $data;
	}

	public function invalidAdapterProvider()
	{
		$file = __DIR__ . '/../_data/invalid.png';
		return [
			[new FileAdapter(new File($file, false))],
			[new LocalPathAdapter($file)],
			[new UploadedFileAdapter(new UploadedFile($file, 'invalid.png', 'image/png', 8444, UPLOAD_ERR_CANT_WRITE, false))],
		];
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
	public function test_it_adapts_filename($adapter, $source)
	{
		$adapter = new $adapter($source);
		$this->assertEquals('plank', $adapter->filename());
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
		$contents = $adapter->contents();
		$this->assertTrue(is_resource($contents));
		$this->assertEquals(get_resource_type($contents), 'stream');
	}

	/**
	 * @dataProvider adapterProvider
	 */
	public function test_it_adapts_file_size($adapter, $source)
	{
		$adapter = new $adapter($source);
		$this->assertEquals(8444, $adapter->size());
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