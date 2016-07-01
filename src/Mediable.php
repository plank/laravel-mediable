<?php

namespace Frasmage\Mediable;

use Illuminate\Database\Eloquent\Builder;

/**
 * HasMedia Trait
 *
 */
trait Mediable
{
    private $media_dirty_tags = [];

    /**
     * @see HasMediaInterface::media()
     */
    public function media()
    {
        return $this->morphToMany(Media::class, 'mediable')->withPivot('tag');
    }

    /**
     * Query scope to detect the presence of one or more attached media for a given tag
     * @param  Builder $q
     * @param  string  $tag
     */
    public function scopeWhereHasMedia(Builder $q, $tags)
    {
        $q->whereHas('media', function ($q) use ($tags) {
            $q->whereIn('tag', (array) $tags);
        });
    }

    /**
     * @see HasMediaInterface::addMedia()
     */
    public function attachMedia($media, $tags)
    {
        if(is_array($tags)){
            foreach($tags as $tag){
                $this->attachMedia($media, $tag);
            }
            return;
        }

        $this->media()->attach($media, ['tag' => $tags]);
        $this->markMediaDirty($tags);
    }

    /**
     * @see HasMediaInterface::replaceMedia()
     */
    public function syncMedia($media, $tags)
    {
        $this->detachMediaTags($tags);
        $this->attachMedia($media, $tags);
    }

    /**
     * @see HasMediaInterface::removeMedia()
     */
    public function detachMedia($media, $tags = null)
    {
        $query = $this->media();
        if ($tags) {
            $query->wherePivotIn('tag', (array) $tags);
        }
        $query->detach($media);
        $this->markMediaDirty($tags);
    }

    /**
     * @see HasMediaInterface::removeMediaForAssociation()
     */
    public function detachMediaTags($tags)
    {
        $this->media()->newPivotStatement()
            ->where($this->media()->getMorphType(), $this->media()->getMorphClass())
            ->where($this->media()->getForeignKey(), $this->getKey())
            ->whereIn('tag', (array) $tags)->delete();
        $this->markMediaDirty($tags);
    }

    /**
     * @see HasMediaInterface::hasMedia()
     */
    public function hasMedia($tags, $match_all = false)
    {
        return count($this->getMedia($tags, $match_all)) > 0;
    }

    /**
     * @see HasMediaInterface::getMedia()
     */
    public function getMedia($tags, $match_all = false)
    {
        if($match_all){
            return $this->getMediaMatchAll($tags);
        }

        $this->rehydrateMediaIfNecessary($tags);
        return $this->media
        // 
        ->filter(function ($media) use ($tags) {
                return in_array($media->pivot->tag, (array) $tags);
        })
        //remove duplicate media
        ->keyBy(function($media){
            return $media->getKey();
        })->values();
    }

    public function getMediaMatchAll($tags){
        $tags = (array) $tags;
        $this->rehydrateMediaIfNecessary($tags);
        return $this->media
        //froup media by tag name
        ->groupBy('pivot.tag')
        //discard unused tags
        ->filter(function($_, $key) use($tags){
            return in_array($key, $tags);
        })
        //remove media not matching each tag
        ->reduce(function($media, $tagged){
            return $media->intersect($tagged);
        }, $this->media)
        //remove duplicate media
        ->keyBy(function($media){
            return $media->getKey();
        })->values();
    }

    /**
     * @see HasMediaInterface::getAllMedia()
     */
    public function getAllMediaByTag()
    {
        $this->rehydrateMediaIfNecessary();
        return $this->media->groupBy('pivot.tag');
    }

    public function getTagsForMedia(Media $media){
        return $this->media->filter(function($item) use ($media){
            return $item->getKey() === $media->getKey();
        })->map(function($item){
            return $item->pivot->tag;
        });
    }

    /**
     * @see HasMediaInterface::firstMedia()
     */
    public function firstMedia($tags, $match_all = false)
    {
        return $this->getMedia($tags, $match_all)->first();
    }

    protected function markMediaDirty($tags)
    {
        foreach((array) $tags as $tag){
            $this->media_dirty_tags[$tag] = $tag;
        }
    }

    protected function mediaIsDirty($tags = null){
        if(is_null($tags)){
            return count($this->media_dirty_tags);
        }else{
            return count(array_intersect((array) $tags, $this->media_dirty_tags));
        }
    }

    protected function rehydrateMediaIfNecessary($tags = null)
    {
        if($this->rehydratesMedia() && $this->mediaIsDirty($tags)){
            $this->load('media');
        }
    }

    protected function rehydratesMedia(){
        if(property_exists($this, 'rehydrates_media')){
            return $this->rehydrates_media;
        }
        return true;
    }

    public function load($relations){
        if (is_string($relations)) {
            $relations = func_get_args();
        }
        if(in_array('media', $relations)){
            $this->media_dirty_tags = [];
        }
        return parent::load($relations);
    }

}
