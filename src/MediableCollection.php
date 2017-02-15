<?php

namespace Plank\Mediable;

use Illuminate\Database\Eloquent\Collection;
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
    public function loadMedia($tags = [], $match_all = false)
    {
        $tags = (array) $tags;

        if (empty($tags)) {
            return $this->load('media');
        }

        if ($match_all) {
            return $this->loadMediaMatchAll($tags);
        }

        $closure = function (MorphToMany $q) use ($tags) {
            $this->wherePivotTagIn($q, $tags);
        };
        $closure = Closure::bind($closure, $this->first(), $this->first());

        return $this->load(['media' => $closure]);
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
}
