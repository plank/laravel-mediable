<?php

use Plank\Mediable\Media;
use Plank\Mediable\Exceptions\MediaMoveException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class MediaTest extends TestCase
{
    public function test_it_has_path_accessors()
    {
        $media = factory(Media::class)->make([
            'disk' => 'tmp',
            'directory' => 'a/b/c',
            'filename' => 'foo.bar',
            'extension' => 'jpg',
        ]);

        $this->assertEquals(storage_path('tmp/a/b/c/foo.bar.jpg'), $media->getAbsolutePath());
        $this->assertEquals('a/b/c/foo.bar.jpg', $media->getDiskPath());
        $this->assertEquals('a/b/c', $media->directory);
        $this->assertEquals('foo.bar.jpg', $media->basename);
        $this->assertEquals('foo.bar', $media->filename);
        $this->assertEquals('jpg', $media->extension);
    }

    public function test_it_can_be_queried_by_directory()
    {
        $this->useDatabase();

        factory(Media::class)->create(['directory' => 'foo']);
        factory(Media::class)->create(['directory' => 'foo']);
        factory(Media::class)->create(['directory' => 'bar']);
        factory(Media::class)->create(['directory' => 'foo/baz']);

        $this->assertEquals(2, Media::inDirectory('tmp', 'foo')->count());
        $this->assertEquals(1, Media::inDirectory('tmp', 'foo/baz')->count());
    }

    public function test_it_can_be_queried_by_directory_recursively()
    {
        $this->useDatabase();

        factory(Media::class)->create(['directory' => 'foo']);
        factory(Media::class)->create(['directory' => 'foo/bar']);
        factory(Media::class)->create(['directory' => 'foo/bar']);
        factory(Media::class)->create(['directory' => 'foo/bar/baz']);

        $this->assertEquals(4, Media::inDirectory('tmp', 'foo', true)->count());
        $this->assertEquals(3, Media::inOrUnderDirectory('tmp', 'foo/bar')->count());
        $this->assertEquals(1, Media::inDirectory('tmp', 'foo/bar/baz', true)->count());
    }

    public function test_it_can_be_queried_by_basename()
    {
        $this->useDatabase();

        factory(Media::class)->create(['filename' => 'foo', 'extension' => 'bar']);
        factory(Media::class)->create(['id' => 99, 'filename' => 'baz', 'extension' => 'bat']);
        factory(Media::class)->create(['filename' => 'bar', 'extension' => 'foo']);

        $this->assertEquals(99, Media::whereBasename('baz.bat')->first()->id);
    }

    public function test_it_can_be_queried_by_path_on_disk()
    {
        $this->useDatabase();

        factory(Media::class)->create([
            'id' => 4,
            'disk' => 'tmp',
            'directory' => 'foo/bar/baz',
            'filename' => 'bat',
            'extension' => 'jpg'
        ]);
        $this->assertEquals(4, Media::forPathOnDisk('tmp', 'foo/bar/baz/bat.jpg')->first()->id);
    }

    public function test_it_can_be_queried_by_path_on_disk_when_directory_is_empty()
    {
        $this->useDatabase();

        factory(Media::class)->create([
            'id' => 4,
            'disk' => 'tmp',
            'directory' => '',
            'filename' => 'bat',
            'extension' => 'jpg'
        ]);
        $this->assertEquals(4, Media::forPathOnDisk('tmp', 'bat.jpg')->first()->id);
    }

    public function test_it_can_view_human_readable_file_size()
    {
        $media = factory(Media::class)->make(['size' => 0]);

        $this->assertEquals('0 B', $media->readableSize());

        $media->size = 1024 * 1024;
        $this->assertEquals('1 MB', $media->readableSize(0));

        $media->size = 1024 * 1024 + 1024 * 100;
        $this->assertEquals('1.1 MB', $media->readableSize(2));
    }

    public function test_it_can_be_checked_for_public_visibility()
    {
        $media = factory(Media::class)->make(['disk' => 'tmp']);
        $this->assertFalse($media->isPubliclyAccessible());

        $media = factory(Media::class)->make(['disk' => 'uploads']);
        $this->assertTrue($media->isPubliclyAccessible());

        $media = factory(Media::class)->make(['disk' => 's3']);
        $this->assertTrue($media->isPubliclyAccessible());

        config()->set('filesystems.disks.s3.visibility', 'hidden');
        $this->assertFalse($media->isPubliclyAccessible());
    }

    public function test_it_can_generate_a_url_to_the_local_file()
    {
        $media = factory(Media::class)->make(['disk' => 'uploads', 'directory' => 'foo/bar', 'filename' => 'baz', 'extension' => 'jpg']);
        $this->assertEquals('http://localhost/uploads/foo/bar/baz.jpg', $media->getUrl());
    }

    public function test_it_can_generate_a_custom_url_to_the_local_file()
    {
        $this->app['config']->set('filesystems.disks.uploads.url', 'http://example.com');
        $media = factory(Media::class)->make(['disk' => 'uploads', 'directory' => 'foo/bar', 'filename' => 'baz', 'extension' => 'jpg']);
        $this->assertEquals('http://example.com/foo/bar/baz.jpg', $media->getUrl());
    }

    public function test_it_can_generate_a_url_to_the_file_on_s3()
    {
        if (!$this->s3ConfigLoaded()) {
            $this->markTestSkipped('S3 Credentials not available.');
        }
        $media = factory(Media::class)->make(['disk' => 's3', 'directory' => 'foo/bar', 'filename' => 'baz', 'extension' => 'jpg']);
        $this->assertEquals('https://s3.amazonaws.com/' . env('S3_BUCKET') . '/foo/bar/baz.jpg', $media->getUrl());
    }

    public function test_it_can_check_if_its_file_exists()
    {
        $this->useFilesystem('tmp');

        $media = factory(Media::class)->make(['disk' => 'tmp']);
        $this->assertFalse($media->fileExists());
        $this->seedFileForMedia($media);
        $this->assertTrue($media->fileExists());
    }

    public function test_it_can_be_moved_on_disk()
    {
        $this->useFilesystem('tmp');
        $this->useDatabase();

        $media = factory(Media::class)->make(['disk' => 'tmp', 'directory' => 'foo', 'filename' => 'bar', 'extension' => 'baz']);
        $this->seedFileForMedia($media);

        $media->move('alpha/beta');
        $this->assertEquals('alpha/beta/bar.baz', $media->getDiskPath());
        $this->assertTrue($media->exists());
        $media->move('', 'gamma.baz');
        $this->assertEquals('gamma.baz', $media->getDiskPath());
        $media->rename('foo.bar');
        $this->assertEquals('foo.bar.baz', $media->getDiskPath());
        $this->assertTrue($media->exists());
    }

    public function test_it_throws_an_exception_if_moving_to_existing_file()
    {
        $this->useFilesystem('tmp');

        $media1 = factory(Media::class)->make([
            'disk' => 'tmp',
            'directory'=> '',
            'filename' => 'foo',
            'extension' => 'baz'
        ]);
        $media2 = factory(Media::class)->make([
            'disk' => 'tmp',
            'directory'=> '',
            'filename' => 'bar',
            'extension' => 'baz'
        ]);
        $this->seedFileForMedia($media1);
        $this->seedFileForMedia($media2);

        $this->expectException(MediaMoveException::class);
        $media1->move('', 'bar.baz');
    }

    public function test_it_can_access_file_contents()
    {
        $this->useFilesystem('tmp');

        $media = factory(Media::class)->make([
            'disk' => 'tmp',
            'extension' => 'html'
        ]);
        $this->seedFileForMedia($media, '<h1>Hello World</h1>');
        $this->assertEquals('<h1>Hello World</h1>', $media->contents());
    }

    public function test_it_deletes_its_file_on_deletion()
    {
        $this->useDatabase();
        $this->useFilesystem('tmp');

        $media = factory(Media::class)->create([
            'disk' => 'tmp',
            'directory' => '',
            'filename' => 'file',
            'extension' => 'txt'
        ]);
        $this->seedFileForMedia($media);
        $path = $media->getAbsolutePath();

        $this->assertFileExists($path);
        $media->delete();
        $this->assertFalse(file_exists($path));
    }

    public function test_it_cascades_relationship_on_delete()
    {
        $this->useDatabase();

        $media = factory(Media::class)->create();
        $mediable = factory(SampleMediable::class)->create();
        $mediable->attachMedia($media, 'foo');

        $media->delete();
        $this->assertEquals(0, $mediable->getMedia('foo')->count());
    }

    public function test_it_doesnt_cascade_relationship_on_soft_delete()
    {
        $this->useDatabase();

        $media = factory(MediaSoftDelete::class)->create();
        $mediable = factory(SampleMediable::class)->create();
        $mediable->attachMedia($media, 'foo');

        $media->delete();
        $this->assertEquals(1, $mediable->getMedia('foo')->count());
    }

    public function test_it_cascades_relationships_on_soft_delete_with_config()
    {
        $this->useDatabase();

        $mediable = factory(SampleMediable::class)->create();
        $media = factory(MediaSoftDelete::class)->create();
        $mediable->attachMedia($media, 'foo');

        config()->set('mediable.detach_on_soft_delete', true);

        $media->delete();
        $this->assertEquals(0, $mediable->getMedia('foo')->count());
    }

    public function test_it_cascades_relationship_on_force_delete()
    {
        $this->useDatabase();

        $mediable = factory(SampleMediableSoftDelete::class)->create();
        $media = factory(Media::class)->create();
        $mediable->attachMedia($media, 'foo');

        $media->forceDelete();
        $this->assertEquals(0, $mediable->getMedia('foo')->count());
    }
}
