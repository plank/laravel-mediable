<?php

namespace Frasmage\Mediable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

/**
 * Mediable Trait
 *
 * Provides functionality for attaching media to an eloquent model.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 * @var boolean $rehydrates_media
 * Whether the model should automatically reload its media relationship after modification.
 */
trait Mediable
{
    private $media_dirty_tags = [];

    /**
     * Relationship for all attached media
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function media()
    {
        return $this->morphToMany(Media::class, 'mediable')->withPivot('tag');
    }

    /**
     * Query scope to detect the presence of one or more attached media for a given tag
     * @param  Builder $q
     * @param  string|array $tags
     * @param  boolean $match_all
     * @return void
     */
    public function scopeWhereHasMedia(Builder $q, $tags, $match_all = false)
    {
        if($match_all && is_array($tags) && count($tags) > 1){
            return $this->scopeWhereHasMediaMatchAll($q, $tags);
        }
        $q->whereHas('media', function ($q) use ($tags) {
            $q->whereIn('tag', (array) $tags);
        });
    }

    /**
     * Query scope to detect the presence of one or more attached media that is bound to all of the specified tags simultaneously
     * @param  Builder $q
     * @param  array $tags
     * @return void
     */
    public function scopeWhereHasMediaMatchAll(Builder $q, array $tags)
    {
        $subquery = $this->media()->newPivotStatement()
            ->selectRaw('count(*)')
            ->where($this->media()->getMorphType(), $this->media()->getMorphClass())
            ->whereRaw($this->media()->getForeignKey() . ' = ' . $this->getQualifiedKeyName())
            ->whereIn('tag', $tags)
            ->groupBy('media_id')
            ->havingRaw('count(media_id) = ?', [count($tags)]);

        $q->addBinding($subquery->getBindings(), 'where')
            ->where(new Expression('(' . $subquery->toSql() . ')'), '>=', 1);
    }

     /**
     * Attach a media entity to the model with one or more tags
     * @param mixed $media
     * Either a string or numeric id, an array of ids, an instance of `Media` or an instance of `\Illuminate\Database\Eloquent\Collection`
     * @param string|array $tags
     * One or more tags to define the relation
     * @return void
     */
    public function attachMedia($media, $tags)
    {
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $this->attachMedia($media, $tag);
            }
            return;
        }

        $this->media()->attach($media, ['tag' => $tags]);
        $this->markMediaDirty($tags);
    }

    /**
     * Replace the existing media collection for the specified tag(s).
     * @param mixed $media
     * @param string|array $tags
     * @return void
     */
    public function syncMedia($media, $tags)
    {
        $this->detachMediaTags($tags);
        $this->attachMedia($media, $tags);
    }

    /**
     * Detach a media item from the model
     * @param  mixed $media
     * @param  string|array|null $tags
     * If provided, will remove the media from the model for the provided tag(s) only
     * If omitted, will remove the media from the media for all tags
     * @return void
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
     * Remove one or more tags from the model, detaching any media using those tags
     * @param  string $tags
     * @return void
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
     * Check if the model has any media attached to one or more tags
     * @param  string|array  $tags
     * @param  boolean $match_all
     * If false, will return true if the model has any attach media for any of the provided tags
     * If true, will return true is the model has any media that are attached to all of provided tags simultaneously
     * @return boolean
     */
    public function hasMedia($tags, $match_all = false)
    {
        return count($this->getMedia($tags, $match_all)) > 0;
    }

    /**
     * Retrieve media attached to the model
     * @param  string|array  $tags
     * @param  boolean $match_all
     * If false, will return media attached to any of the provided tags
     * If true, will return media attached to all of the provided tags simultaneously
     * @return boolean
     */
    public function getMedia($tags, $match_all = false)
    {
        if ($match_all) {
            return $this->getMediaMatchAll($tags);
        }

        $this->rehydrateMediaIfNecessary($tags);
        return $this->media
        //exclude media not matching at least one tag
        ->filter(function ($media) use ($tags) {
                return in_array($media->pivot->tag, (array) $tags);
        })
        //remove duplicate media
        ->keyBy(function ($media) {
            return $media->getKey();
        })->values();
    }

    /**
     * Retrieve media attached to multiple tags simultaneously
     * @param array  $tags
     * @return boolean
     */
    public function getMediaMatchAll(array $tags)
    {
        $this->rehydrateMediaIfNecessary($tags);

        //group all tags for each media
        $model_tags = $this->media->reduce(function ($carry, $media) {
            $carry[$media->getKey()][] = $media->pivot->tag;
            return $carry;
        }, []);

        //exclude media not matching all tags
        return $this->media->filter(function ($media) use ($tags, $model_tags) {
           return count(array_intersect($tags, $model_tags[$media->getKey()])) === count($tags);
        })
        //remove duplicate media
        ->keyBy(function ($media) {
            return $media->getKey();
        })->values();
        ;
    }

    /**
     * Shorthand for retrieving a single attached media
     * @param  string|array  $tags
     * @param  boolean $match_all
     * @see self::getMedia()
     * @return boolean
     */
    public function firstMedia($tags, $match_all = false)
    {
        return $this->getMedia($tags, $match_all)->first();
    }

    /**
     * Retrieve all media grouped by tag name
     * @return \Illuminate\Support\Collection
     */
    public function getAllMediaByTag()
    {
        $this->rehydrateMediaIfNecessary();
        return $this->media->groupBy('pivot.tag');
    }

    /**
     * Get a list of all tags that the media is attached to.
     * @param  Media  $media
     * @return array
     */
    public function getTagsForMedia(Media $media)
    {
        return $this->media->reduce(function ($carry, $item) use ($media) {
            if ($item->getKey() === $media->getKey()) {
                $carry[] = $item->pivot->tag;
            }
            return $carry;
        }, []);
    }

    /**
     * Indicate that the media attached to the provided tags has been modified
     * @param  string|array $tags
     * @return void
     */
    protected function markMediaDirty($tags)
    {
        foreach ((array) $tags as $tag) {
            $this->media_dirty_tags[$tag] = $tag;
        }
    }

    /**
     * Check if media attached to the specified tags has been modified
     * @param  null|string|array $tags
     * If omitted, will return `true` if any tags have been modified
     * @return boolean
     */
    protected function mediaIsDirty($tags = null)
    {
        if (is_null($tags)) {
            return count($this->media_dirty_tags);
        } else {
            return count(array_intersect((array) $tags, $this->media_dirty_tags));
        }
    }

    /**
     * Reloads media relationship if allowed and necessary
     * @param  null|string|array $tags
     * @return void
     */
    protected function rehydrateMediaIfNecessary($tags = null)
    {
        if ($this->rehydratesMedia() && $this->mediaIsDirty($tags)) {
            $this->load('media');
        }
    }

    /**
     * Check whether the model is allowed to automatically reload media relationship
     *
     * Can be overridden by setting protected property `$rehydrates_media` on the model.
     * @return boolean
     */
    protected function rehydratesMedia()
    {
        if (property_exists($this, 'rehydrates_media')) {
            return $this->rehydrates_media;
        }
        return config('mediable.rehydrate_media', true);
    }

    /**
     * {@inheritDoc}
     */
    public function load($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }
        if (in_array('media', $relations)) {
            $this->media_dirty_tags = [];
        }
        return parent::load($relations);
    }
}
