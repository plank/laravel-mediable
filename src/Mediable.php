<?php

namespace Plank\Mediable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;

/**
 * Mediable Trait.
 *
 * Provides functionality for attaching media to an eloquent model.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 *
 * Whether the model should automatically reload its media relationship after modification.
 */
trait Mediable
{
    /**
     * List of media tags that have been modified since last load.
     * @var array
     */
    private $media_dirty_tags = [];

    /**
     * Boot the Mediable trait.
     *
     * @return void
     */
    public static function bootMediable()
    {
        static::deleted(function (Model $model) {
            $model->handleMediableDeletion();
        });
    }

    /**
     * Relationship for all attached media.
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function media()
    {
        return $this->morphToMany(config('mediable.model'), 'mediable')
            ->withPivot('tag', 'order')
            ->orderBy('order');
    }

    /**
     * Query scope to detect the presence of one or more attached media for a given tag.
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string|array                          $tags
     * @param  bool                                  $match_all
     * @return void
     */
    public function scopeWhereHasMedia(Builder $q, $tags, $match_all = false)
    {
        if ($match_all && is_array($tags) && count($tags) > 1) {
            return $this->scopeWhereHasMediaMatchAll($q, $tags);
        }
        $q->whereHas('media', function (Builder $q) use ($tags) {
            $q->whereIn('tag', (array) $tags);
        });
    }

    /**
     * Query scope to detect the presence of one or more attached media that is bound to all of the specified tags simultaneously.
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  array                                 $tags
     * @return void
     */
    public function scopeWhereHasMediaMatchAll(Builder $q, array $tags)
    {
        $grammar = $q->getQuery()->getGrammar();
        $subquery = $this->newMatchAllQuery($tags)
            ->selectRaw('count(*)')
            ->whereRaw($grammar->wrap($this->mediaQualifiedForeignKey()).' = '.$grammar->wrap($this->getQualifiedKeyName()));
        $q->whereRaw('('.$subquery->toSql().') >= 1', $subquery->getBindings());
    }

    /**
     * Query scope to eager load attached media.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string|array                          $tags      If one or more tags are specified, only media attached to those tags will be loaded.
     * @param  bool                                  $match_all Only load media matching all provided tags
     * @return void
     */
    public function scopeWithMedia(Builder $q, $tags = [], $match_all = false)
    {
        $tags = (array) $tags;

        if (empty($tags)) {
            return $q->with('media');
        }

        if ($match_all) {
            return $q->withMediaMatchAll($tags);
        }

        $q->with(['media' => function (MorphToMany $q) use ($tags) {
            $this->wherePivotTagIn($q, $tags);
        }]);
    }

    /**
     * Query scope to eager load attached media assigned to multiple tags.
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string|array                          $tags
     * @return void
     */
    public function scopeWithMediaMatchAll(Builder $q, $tags = [])
    {
        $tags = (array) $tags;
        $q->with(['media' => function (MorphToMany $q) use ($tags) {
            $this->addMatchAllToEagerLoadQuery($q, $tags);
        }]);
    }

    /**
     * Lazy eager load attached media relationships.
     * @param  string|array  $tags      If one or more tags are specified, only media attached to those tags will be loaded.
     * @param  bool          $match_all Only load media matching all provided tags
     * @return $this
     */
    public function loadMedia($tags = [], $match_all = false)
    {
        $tags = (array) $tags;

        if (empty($tags)) {
            return $this->load('media');
        }

        if ($match_all) {
            return $this->loadMediaMatchAll($tags);
        }

        $this->load(['media' => function (MorphToMany $q) use ($tags) {
            $this->wherePivotTagIn($q, $tags);
        }]);

        return $this;
    }

    /**
     * Lazy eager load attached media relationships matching all provided tags.
     * @param  string|array  $tags one or more tags
     * @return $this
     */
    public function loadMediaMatchAll($tags = [])
    {
        $tags = (array) $tags;
        $this->load(['media' => function (MorphToMany $q) use ($tags) {
            $this->addMatchAllToEagerLoadQuery($q, $tags);
        }]);

        return $this;
    }

    /**
     * Attach a media entity to the model with one or more tags.
     * @param mixed        $media Either a string or numeric id, an array of ids, an instance of `Media` or an instance of `\Illuminate\Database\Eloquent\Collection`
     * @param string|array $tags  One or more tags to define the relation
     * @return void
     */
    public function attachMedia($media, $tags)
    {
        $tags = (array) $tags;
        $increments = $this->getOrderValueForTags($tags);
        $ids = $this->extractIds($media);

        foreach ($tags as $tag) {
            $attach = [];
            foreach ($ids as $id) {
                $attach[$id] = [
                    'tag' => $tag,
                    'order' => ++$increments[$tag],
                ];
            }
            $this->media()->attach($attach);
        }

        $this->markMediaDirty($tags);
    }

    /**
     * Replace the existing media collection for the specified tag(s).
     * @param mixed        $media
     * @param string|array $tags
     * @return void
     */
    public function syncMedia($media, $tags)
    {
        $this->detachMediaTags($tags);
        $this->attachMedia($media, $tags);
    }

    /**
     * Detach a media item from the model.
     * @param  mixed             $media
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
     * Remove one or more tags from the model, detaching any media using those tags.
     * @param  string|array $tags
     * @return void
     */
    public function detachMediaTags($tags)
    {
        $this->media()->newPivotStatement()
            ->where($this->media()->getMorphType(), $this->media()->getMorphClass())
            ->where($this->mediaQualifiedForeignKey(), $this->getKey())
            ->whereIn('tag', (array) $tags)->delete();
        $this->markMediaDirty($tags);
    }

    /**
     * Check if the model has any media attached to one or more tags.
     * @param  string|array  $tags
     * @param  bool          $match_all
     * If false, will return true if the model has any attach media for any of the provided tags
     * If true, will return true is the model has any media that are attached to all of provided tags simultaneously
     * @return bool
     */
    public function hasMedia($tags, $match_all = false)
    {
        return count($this->getMedia($tags, $match_all)) > 0;
    }

    /**
     * Retrieve media attached to the model.
     * @param  string|array  $tags
     * @param  bool          $match_all
     * If false, will return media attached to any of the provided tags
     * If true, will return media attached to all of the provided tags simultaneously
     * @return bool
     */
    public function getMedia($tags, $match_all = false)
    {
        if ($match_all) {
            return $this->getMediaMatchAll($tags);
        }

        $this->rehydrateMediaIfNecessary($tags);

        return $this->media
        //exclude media not matching at least one tag
        ->filter(function (Media $media) use ($tags) {
            return in_array($media->pivot->tag, (array) $tags);
        })
        //remove duplicate media
        ->keyBy(function (Media $media) {
            return $media->getKey();
        })->values();
    }

    /**
     * Retrieve media attached to multiple tags simultaneously.
     * @param array  $tags
     * @return bool
     */
    public function getMediaMatchAll(array $tags)
    {
        $this->rehydrateMediaIfNecessary($tags);

        //group all tags for each media
        $model_tags = $this->media->reduce(function ($carry, Media $media) {
            $carry[$media->getKey()][] = $media->pivot->tag;

            return $carry;
        }, []);

        //exclude media not matching all tags
        return $this->media->filter(function (Media $media) use ($tags, $model_tags) {
            return count(array_intersect($tags, $model_tags[$media->getKey()])) === count($tags);
        })
        //remove duplicate media
        ->keyBy(function (Media $media) {
            return $media->getKey();
        })->values();
    }

    /**
     * Shorthand for retrieving the first attached media item.
     * @param  string|array  $tags
     * @param  bool         $match_all
     * @see \Plank\Mediable\Mediable::getMedia()
     * @return bool
     */
    public function firstMedia($tags, $match_all = false)
    {
        return $this->getMedia($tags, $match_all)->first();
    }

    /**
     * Shorthand for retrieving the last attached media item.
     * @param  string|array  $tags
     * @param  bool         $match_all
     * @see \Plank\Mediable\Mediable::getMedia()
     * @return bool
     */
    public function lastMedia($tags, $match_all = false)
    {
        return $this->getMedia($tags, $match_all)->last();
    }

    /**
     * Retrieve all media grouped by tag name.
     * @return \Illuminate\Support\Collection
     */
    public function getAllMediaByTag()
    {
        $this->rehydrateMediaIfNecessary();

        return $this->media->groupBy('pivot.tag');
    }

    /**
     * Get a list of all tags that the media is attached to.
     * @param  \Plank\Mediable\Media  $media
     * @return array
     */
    public function getTagsForMedia(Media $media)
    {
        $this->rehydrateMediaIfNecessary();

        return $this->media->reduce(function ($carry, Media $item) use ($media) {
            if ($item->getKey() === $media->getKey()) {
                $carry[] = $item->pivot->tag;
            }

            return $carry;
        }, []);
    }

    /**
     * Indicate that the media attached to the provided tags has been modified.
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
     * Check if media attached to the specified tags has been modified.
     * @param  null|string|array $tags
     * If omitted, will return `true` if any tags have been modified
     * @return bool
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
     * Reloads media relationship if allowed and necessary.
     * @param  null|string|array $tags
     * @return void
     */
    protected function rehydrateMediaIfNecessary($tags = null)
    {
        if ($this->rehydratesMedia() && $this->mediaIsDirty($tags)) {
            $this->loadMedia();
        }
    }

    /**
     * Check whether the model is allowed to automatically reload media relationship.
     *
     * Can be overridden by setting protected property `$rehydrates_media` on the model.
     * @return bool
     */
    protected function rehydratesMedia()
    {
        if (property_exists($this, 'rehydrates_media')) {
            return $this->rehydrates_media;
        }

        return config('mediable.rehydrate_media', true);
    }

    /**
     * Generate a query builder for.
     * @param  array|string  $tags [description]
     * @return [type]              [description]
     */
    protected function newMatchAllQuery($tags = [])
    {
        $tags = (array) $tags;
        $grammar = $this->media()->getBaseQuery()->getGrammar();
        return $this->media()->newPivotStatement()
            ->where($this->media()->getMorphType(), $this->media()->getMorphClass())
            ->whereIn('tag', $tags)
            ->groupBy($this->mediaQualifiedRelatedKey())
            ->havingRaw('count('.$grammar->wrap($this->mediaQualifiedRelatedKey()).') = '.count($tags));
    }

    /**
     * Modify an eager load query to only load media assigned to all provided tags simultaneously.
     * @param  \Illuminate\Database\Eloquent\Relations\MorphToMany $q
     * @param  array|string                                        $tags
     * @return void
     */
    protected function addMatchAllToEagerLoadQuery(MorphToMany $q, $tags = [])
    {
        $tags = (array) $tags;
        $grammar = $q->getBaseQuery()->getGrammar();
        $subquery = $this->newMatchAllQuery($tags)->select($this->mediaQualifiedRelatedKey());
        $q->whereRaw($grammar->wrap($this->mediaQualifiedRelatedKey()).' IN ('.$subquery->toSql().')', $subquery->getBindings());
        $this->wherePivotTagIn($q, $tags);
    }

    /**
     * Determine whether media relationships should be detached when the model is deleted or soft deleted.
     * @return void
     */
    protected function handleMediableDeletion()
    {
        // only cascade soft deletes when configured
        if (static::hasGlobalScope(SoftDeletingScope::class) && ! $this->forceDeleting) {
            if (config('mediable.detach_on_soft_delete')) {
                $this->media()->detach();
            }
            // always cascade for hard deletes
        } else {
            $this->media()->detach();
        }
    }

    /**
     * Determine the highest order value assigned to each provided tag.
     * @param  string|array $tags
     * @return int
     */
    private function getOrderValueForTags($tags)
    {
        $q = $this->media()->newPivotStatement();
        $tags = (array) $tags;
        $grammar = $q->getGrammar();

        $result = $q->selectRaw($grammar->wrap('tag').', max('.$grammar->wrap('order').') as aggregate')
            ->where('mediable_type', $this->getMorphClass())
            ->where('mediable_id', $this->getKey())
            ->whereIn('tag', $tags)
            ->groupBy('tag')
            ->pluck('aggregate', 'tag');

        $empty = array_combine($tags, array_fill(0, count($tags), 0));

        return array_merge($empty, collect($result)->toArray());
    }

    /**
     * Convert mixed input to array of ids.
     * @param  mixed $input
     * @return array
     */
    private function extractIds($input)
    {
        if ($input instanceof Collection) {
            return $input->modelKeys();
        }

        if ($input instanceof Media) {
            return [$input->getKey()];
        }

        return (array) $input;
    }

    /**
     * {@inheritdoc}
     */
    public function load($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }

        if (array_key_exists('media', $relations) || in_array('media', $relations)) {
            $this->media_dirty_tags = [];
        }

        return parent::load($relations);
    }

    /**
     * {@inheritdoc}
     * @return \Plank\Mediable\MediableCollection
     */
    public function newCollection(array $models = [])
    {
        return new MediableCollection($models);
    }

    /**
     * Key the name of the foreign key field of the media relation
     *
     * Accounts for the change of method name in Laravel 5.4
     *
     * @return string
     */
    private function mediaQualifiedForeignKey()
    {
        $relation = $this->media();
        if (method_exists($relation, 'getQualifiedForeignPivotKeyName')) {
            return $relation->getQualifiedForeignPivotKeyName();
        } elseif (method_exists($relation, 'getQualifiedForeignKeyName')) {
            return $relation->getQualifiedForeignKeyName();
        }
        return $relation->getForeignKey();
    }

    /**
     * Key the name of the related key field of the media relation
     *
     * Accounts for the change of method name in Laravel 5.4 and again in Laravel 5.5
     *
     * @return string
     */
    private function mediaQualifiedRelatedKey()
    {
        $relation = $this->media();
        // Laravel 5.5
        if (method_exists($relation, 'getQualifiedRelatedPivotKeyName')) {
            return $relation->getQualifiedRelatedPivotKeyName();
            // Laravel 5.4
        } elseif (method_exists($relation, 'getQualifiedRelatedKeyName')) {
            return $relation->getQualifiedRelatedKeyName();
        }
        // Laravel <= 5.3
        return $relation->getOtherKey();
    }

    /**
     * perform a WHERE IN on the pivot table's tags column
     *
     * Adds support for Laravel <= 5.2, which does not provide a `wherePivotIn()` method
     * @param  MorphToMany $q
     * @param  array       $tags
     * @return void
     */
    private function wherePivotTagIn(MorphToMany $q, $tags = [])
    {
        method_exists($q, 'wherePivotIn') ? $q->wherePivotIn('tag', $tags) : $q->whereIn($this->media()->getTable().'.tag', $tags);
    }
}
