<?php

use Frasmage\Mediable\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @covers Frasmage\Mediable\Mediable
 */
class MediableTest extends TestCase
{
    public function test_it_can_be_related_to_media()
    {
        $mediable = factory(SampleMediable::class)->make();
        $relationship = $mediable->media();

        $this->assertInstanceOf(MorphToMany::class, $relationship);
        $this->assertEquals('mediable_type', $relationship->getMorphType());
        $this->assertEquals('mediables', $relationship->getTable());
        $this->assertEquals('mediables.mediable_id', $relationship->getForeignKey());
        $this->assertEquals('sample_mediables.id', $relationship->getQualifiedParentKeyName());
    }

    public function test_it_can_attach_and_retrieve_media_by_a_tag()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 2]);

        $mediable->attachMedia($media1, 'foo');
        $result = $mediable->getMedia('foo');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
        $this->assertEquals([2], $result->pluck('id')->toArray());
    }

    public function test_it_can_attach_one_media_to_multiple_tags()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 2]);

        $mediable->attachMedia($media1, 'bar');
        $mediable->attachMedia($media1, 'foo');

        $this->assertEquals([2], $mediable->getMedia('foo')->pluck('id')->toArray());
        $this->assertEquals([2], $mediable->getMedia('bar')->pluck('id')->toArray());
        $this->assertEquals(0, $mediable->getMedia('baz')->count(), 'Found media for non-existent tag');
    }

    public function test_it_can_attach_multiple_media_to_multiple_tags_simultaneously()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create();
        $media2 = factory(Media::class)->create();

        $mediable->attachMedia([$media1->id, $media2->id], ['foo', 'bar']);

        $this->assertCount(2, $mediable->getMedia('foo'));
        $this->assertCount(2, $mediable->getMedia('bar'));
    }

    public function test_it_can_find_the_first_media()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 1]);
        $media2 = factory(Media::class)->create(['id' => 2]);

        $mediable->attachMedia($media1, 'foo');
        $mediable->attachMedia($media2, 'foo');

        $this->assertEquals(1, $mediable->firstMedia('foo')->id);
    }

    public function test_it_can_find_media_matching_any_tags()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 1]);
        $media2 = factory(Media::class)->create(['id' => 2]);
        $media3 = factory(Media::class)->create(['id' => 3]);

        $mediable->attachMedia($media1, 'foo');
        $mediable->attachMedia($media1, 'bar');
        $mediable->attachMedia($media2, 'bar');
        $mediable->attachMedia($media3, 'baz');

        $this->assertEquals([1, 2], $mediable->getMedia(['foo', 'bar'], false)->pluck('id')->toArray());
    }

    public function test_it_can_find_media_matching_multiple_tags()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 1]);
        $media2 = factory(Media::class)->create(['id' => 2]);
        $media3 = factory(Media::class)->create(['id' => 3]);

        $mediable->attachMedia($media1, 'foo');
        $mediable->attachMedia($media1, 'bar');
        $mediable->attachMedia($media2, 'bar');
        $mediable->attachMedia($media3, 'baz');

        $this->assertEquals([1], $mediable->getMedia(['foo', 'bar'], true)->pluck('id')->toArray());
        $this->assertEquals(0, $mediable->getMedia(['foo', 'bat'], true)->count());
    }

    public function test_it_can_check_presence_of_attached_media()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 1]);
        $media2 = factory(Media::class)->create(['id' => 2]);

        $mediable->attachMedia($media1, 'foo');
        $mediable->attachMedia($media2, 'foo');
        $mediable->attachMedia($media2, 'bar');

        $this->assertTrue($mediable->hasMedia('foo'));
        $this->assertTrue($mediable->hasMedia('bar'));
        $this->assertFalse($mediable->hasMedia('baz'));
        $this->assertTrue($mediable->hasMedia(['bar', 'baz'], false), 'Failed to find model matching one of many tag');
        $this->assertFalse($mediable->hasMedia(['bar', 'baz'], true), 'Failed to match all tags');
    }

    public function test_it_can_list_media_by_tag()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 1]);
        $media2 = factory(Media::class)->create(['id' => 2]);

        $mediable->attachMedia($media1, 'foo');
        $mediable->attachMedia($media2, 'bar');

        $result = $mediable->getAllMediaByTag();
        $this->assertEquals(['foo', 'bar'], $result->keys()->toArray());
        $this->assertEquals([1], $result['foo']->pluck('id')->toArray());
        $this->assertEquals([2], $result['bar']->pluck('id')->toArray());
    }

    public function test_it_can_detach_media_by_tag()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media = factory(Media::class)->create();
        $mediable->attachMedia($media, 'foo');
        $mediable->detachMedia($media, 'foo');

        $this->assertEquals(0, $mediable->getMedia('foo')->count());
    }

    public function test_it_can_detach_media_of_multiple_tags()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id'=>1]);
        $media2 = factory(Media::class)->create(['id'=>2]);
    }

    public function test_it_can_sync_media_by_tag()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 2]);
        $media2 = factory(Media::class)->create(['id' => 3]);

        $mediable->attachMedia([$media1->id, $media2->id], 'foo');
        $mediable->attachMedia($media2, 'bar');
        $mediable->syncMedia($media1, 'foo');

        $this->assertEquals(1, $mediable->getMedia('foo')->count());
        $this->assertEquals([3], $mediable->getMedia('bar')->pluck('id')->toArray(), 'Modified other tags');
    }

    public function test_it_can_sync_media_to_multiple_tags()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 2]);
        $media2 = factory(Media::class)->create(['id' => 3]);
        $media3 = factory(Media::class)->create(['id' => 4]);

        $mediable->attachMedia($media1, 'foo');
        $mediable->attachMedia($media1, 'bar');

        $mediable->syncMedia([$media2->id, $media3->id], ['bar', 'baz']);

        $this->assertEquals([2], $mediable->getMedia('foo')->pluck('id')->toArray());
        $this->assertEquals([3, 4], $mediable->getMedia('bar')->pluck('id')->toArray());
        $this->assertEquals([3, 4], $mediable->getMedia('baz')->pluck('id')->toArray());
    }

    public function test_it_can_be_queried_by_tag()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media = factory(Media::class)->create();
        $mediable->attachMedia($media, 'foo');

        $this->assertEquals(1, SampleMediable::whereHasMedia('foo', false)->count());
        $this->assertEquals(0, SampleMediable::whereHasMedia('bar', false)->count(), 'Queriable by non-existent group');
    }

    public function test_it_can_be_queried_by_tag_matching_all()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 1]);
        $media2 = factory(Media::class)->create(['id' => 2]);

        $mediable->attachMedia($media1, ['foo', 'bar']);
        $mediable->attachMedia($media2, ['foo']);

        $this->assertEquals([1], SampleMediable::whereHasMediaMatchAll(['foo', 'bar'])->get()->pluck('id')->toArray());
        $this->assertEquals([1], SampleMediable::whereHasMedia(['foo', 'bar'], true)->get()->pluck('id')->toArray());
    }

    public function test_it_can_list_the_tags_a_media_is_attached_to()
    {
        $mediable = factory(SampleMediable::class)->create();
        $media = factory(Media::class)->create();

        $mediable->attachMedia($media, 'foo');
        $mediable->attachMedia($media, 'bar');

        $this->assertEquals(['foo', 'bar'], $mediable->getTagsForMedia($media));
    }

    public function test_it_can_disable_automatic_rehydration()
    {
        $mediable = factory(SampleMediable::class)->create();
        $mediable->rehydrates_media = false;
        $media = factory(Media::class)->create();

        $mediable->media;
        $mediable->attachMedia($media, 'foo');
        $this->assertEquals(0, $mediable->getMedia('foo')->count());
    }
}
