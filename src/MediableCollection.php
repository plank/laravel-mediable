<?php

namespace Plank\Mediable;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class MediableCollection extends Collection
{
    /**
     * Lazy eager load media attached to items in the collection
     * @param  array  $tags
     * If one or more tags are specified, only media attached to those tags will be loaded.
     * @return $this
     */
    public function loadMedia($tags = [])
    {
        $tags = (array)$tags;

        if(empty($tags)){
            return $this->load('media');
        }

        return $this->load(['media' => function(MorphToMany $q) use($tags){
            $q->wherePivotIn('tag', $tags);
        }]);
    }
}
