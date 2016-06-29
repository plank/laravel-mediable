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
        $this->assertEquals('mediables.mediable_id', $relationship->getForeignKey());
        $this->assertEquals('sample_mediables.id', $relationship->getQualifiedParentKeyName());
    }

    public function test_it_can_add_media(){
        $mediable = factory(SampleMediable::class)->create();
        $media1 = factory(Media::class)->create();
        $media2 = factory(Media::class)->create();

        $mediable->addMedia($media1, 'foo');
        $this->assertEquals(1, $mediable->getMedia('foo')->count());

        $mediable->addMedia($media2, 'foo');
        $this->assertEquals(2, $mediable->getMedia('foo')->count());
    }

    public function test_it_can_replace_media(){

    }

    public function test_it_can_be_queried_media_association_type(){
        $mediable = factory(SampleMediable::class)->create();
        $media = factory(Media::class)->create();
        $mediable->addMedia($media, 'foo');

        $this->assertEquals(1, SampleMediable::whereHasMedia('foo')->count());
        $this->assertEquals(0, SampleMediable::whereHasMedia('bar')->count());
    }

}
