<?php

namespace Plank\Mediable\Tests\Mocks;

use Illuminate\Database\Eloquent\Model;
use Plank\Mediable\MediableInterface;
use Plank\Mediable\Mediable;

/**
 * @method static self first()
 */
class SampleMediable extends Model implements MediableInterface
{
    use Mediable;

    public $rehydrates_media = true;
    /**
     * Lazy eager load attached media relationships matching all provided tags.
     * @param  string|string[] $tags one or more tags
     * @param bool $withVariants If true, also load the variants and/or originalMedia relation of each Media
     * @return $this
     */
    public function loadMediaMatchAll($tags = [], bool $withVariants = false): self
    {
        return $this->loadMedia($tags, true, $withVariants);
    }

    /**
     * Lazy eager load attached media relationships matching all provided tags, as well
     * as the variants of those media.
     * @param array $tags
     * @return $this
     */
    public function loadMediaWithVariantsMatchAll($tags = []): self
    {
        return $this->loadMedia($tags, true, true);
    }

    /** Lazy eager load attached media, as well as their variants.
     * @param array $tags
     * @param bool $matchAll
     * @return $this
     */
    public function loadMediaWithVariants($tags = [], bool $matchAll = false): self
    {
        return $this->loadMedia($tags, $matchAll, true);
    }
}
