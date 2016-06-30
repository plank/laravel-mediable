<?php

use Frasmage\Mediable\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class MediableTest extends TestCase
{
    public function test_it_can_be_related_to_media(){
        $mediable = factory(SampleMediable::class)->make();
        $relationship = $mediable->media();

        $this->assertInstanceOf(MorphToMany::class, $relationship);
        $this->assertEquals('mediable_type', $relationship->getMorphType());
        $this->assertEquals('mediables', $relationship->getTable());
        $this->assertEquals('mediables.mediable_id', $relationship->getForeignKey());
        $this->assertEquals('sample_mediables.id', $relationship->getQualifiedParentKeyName());
    }

    public function test_it_can_attach_and_retrieve_media_by_association(){
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 9]);

        $mediable->attachMedia($media1, 'foo');
        $result = $mediable->getMedia('foo');
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertEquals(9, $result->first()->id);
    }

    public function test_it_can_attach_one_media_to_multiple_associations(){
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 9]);

        $mediable->attachMedia($media1, 'bar');
        $mediable->attachMedia($media1, 'foo');
        $this->assertEquals(9, $mediable->getMedia('foo')->first()->id);
        $this->assertEquals(9, $mediable->getMedia('bar')->first()->id);
        $this->assertEquals(0, $mediable->getMedia('baz')->count(), 'Found media for non-existent association');
    }

    public function test_it_can_sync_media_by_association(){
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create(['id' => 9]);
        $media2 = factory(Media::class)->create(['id' => 10]);

        $mediable->attachMedia([$media1, $media2], 'foo');
        $mediable->attachMedia($media2, 'bar');
        $mediable->syncMedia($media1, 'foo');
        $mediable->load('media');
        $this->assertEquals(1, $mediable->getMedia('foo')->count());
        $this->assertEquals(10, $mediable->getMedia('bar')->first()->id, 'Modified other associations');
    }

    public function test_it_can_detach_media_by_id(){
        $mediable = factory(SampleMediable::class)->create();
        $media = factory(Media::class)->create(['id'=>1]);
        $mediable->attachMedia($media, 'foo');
        $mediable->detachMedia($media, 'foo');
        $this->assertEquals(0, $mediable->getMedia('foo')->count());
    }

    public function test_it_can_be_queried_media_association_type(){
        $mediable = factory(SampleMediable::class)->create();
        $media = factory(Media::class)->create();
        $mediable->attachMedia($media, 'foo');

        $this->assertEquals(1, SampleMediable::whereHasMedia('foo')->count());
        $this->assertEquals(0, SampleMediable::whereHasMedia('bar')->count(), 'Queriable by non-existent group');
    }

}
