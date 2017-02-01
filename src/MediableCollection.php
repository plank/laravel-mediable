<?php

namespace Plank\Mediable;

use Illuminate\Database\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Closure;

/**
 * Collection of Mediable Models.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediableCollection extends Collection
{
    /**
     * Lazy eager load media attached to items in the collection.
     * @param  array  $tags
     * If one or more tags are specified, only media attached to those tags will be loaded.
     * @param bool $match_all If true, only load media attached to all tags simultaneously
     * @return $this
     */
    public function loadMedia($tags = [], bool $match_all = false)
    {
        $tags = (array) $tags;

        if (empty($tags)) {
            return $this->load('media');
        }

        if ($match_all) {
            return $this->loadMediaMatchAll($tags);
        }

        return $this->load(['media' => function (MorphToMany $q) use ($tags) {
            $q->wherePivotIn('tag', $tags);
        }]);
    }

    /**
     * Lazy eager load media attached to items in the collection bound all of the provided tags simultaneously.
     * @param  array  $tags
     * If one or more tags are specified, only media attached to those tags will be loaded.
     * @return $this
     */
    public function loadMediaMatchAll($tags = [])
    {
        $tags = (array) $tags;
        $closure = function (MorphToMany $q) use ($tags) {
            $this->addMatchAllToEagerLoadQuery($q, $tags);
        };
        $closure = Closure::bind($closure, $this->first(), $this->first());

        return $this->load(['media' => $closure]);
    }

    public function delete()
    {
        if(count($this) == 0){
            return;
        }

        $relation = $this->first()->media();
        $query = $relation->newPivotStatement();
        $classes = collect();

        $this->each(function(Model $item) use($query, $relation, $classes){
            // collect list of ids of each class in case not all
            // items belong to the same class
            $classes[get_class($item)][] = $item->getKey();

            // select pivots matching each item for deletion
            $query->orWhere(function(Builder $q) use($item, $relation) {
                $q->where($relation->getMorphType(), get_class($item));
                $q->where($relation->getForeignKey(), $item->getKey());
            });
        });

        // delete pivots
        $query->delete();

        // delete each item by class
        $classes->each(function(array $ids, string $class) {
            $class::whereIn((new $class)->getKeyName(), $ids)->delete();
        });
    }

}
