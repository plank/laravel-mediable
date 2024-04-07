<?php

namespace Plank\Mediable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @method static Builder withMedia($tags = [], bool $matchAll = false, bool $withVariants = false)
 * @method static Builder withMediaAndVariants($tags = [], bool $matchAll = false)
 * @method static Builder withMediaMatchAll($tags = [], bool $withVariants = false)
 * @method static Builder withMediaAndVariantsMatchAll($tags = [])
 * @method static Builder whereHasMedia($tags = [], bool $matchAll = false)
 * @method static Builder whereHasMediaMatchAll($tags)
 */
interface MediableInterface
{
    public function media(): MorphToMany;

    /**
     * @param Builder $q
     * @param string|string[] $tags
     * @param bool $matchAll
     * @return void
     */
    public function scopeWhereHasMedia(
        Builder $q,
        $tags = [],
        bool $matchAll = false
    ): void;

    public function scopeWhereHasMediaMatchAll(Builder $q, array $tags): void;

    /**
     * @param Builder $q
     * @param string|string[] $tags
     * @param bool $matchAll
     * @param bool $withVariants
     * @return mixed
     */
    public function scopeWithMedia(
        Builder $q,
        $tags = [],
        bool $matchAll = false,
        bool $withVariants = false
    );

    /**
     * @param Builder $q
     * @param string|string[] $tags
     * @param bool $matchAll
     * @return mixed
     */
    public function scopeWithMediaAndVariants(
        Builder $q,
        $tags = [],
        bool $matchAll = false
    );

    /**
     * @param Builder $q
     * @param string|string[]$tags
     * @param bool $withVariants
     * @return mixed
     */
    public function scopeWithMediaMatchAll(
        Builder $q,
        $tags = [],
        bool $withVariants = false
    );

    /**
     * @param Builder $q
     * @param string|string[] $tags
     * @return void
     */
    public function scopeWithMediaAndVariantsMatchAll(Builder $q, $tags = []): void;

    public function loadMedia();

    /**
     * @param string|string[] $tags
     * @param bool $matchAll
     * @return self
     */
    public function loadMediaWithVariants($tags = [], bool $matchAll = false): self;

    /**
     * @param string|string[] $tags
     * @param bool $withVariants
     * @return self
     */
    public function loadMediaMatchAll($tags = [], bool $withVariants = false): self;

    /**
     * @param string|string[] $tags
     * @return self
     */
    public function loadMediaWithVariantsMatchAll($tags = []): self;

    /**
     * @param string|int|int[]|Media|Collection $media
     * @param string|string[] $tags
     * @return void
     */
    public function attachMedia($media, $tags): void;

    /**
     * @param string|int|int[]|Media|Collection $media
     * @param string|string[] $tags
     * @return void
     */
    public function syncMedia($media, $tags): void;

    /**
     * @param string|int|int[]|Media|Collection $media
     * @param string|string[] $tags
     * @return void
     */
    public function detachMedia($media, $tags = null): void;

    /**
     * @param string|string[] $tags
     * @return void
     */
    public function detachMediaTags($tags): void;

    /**
     * @param string|string[] $tags
     * @param bool $matchAll
     * @return bool
     */
    public function hasMedia($tags, bool $matchAll = false): bool;

    /**
     * @param string|string[] $tags
     * @param bool $matchAll
     * @return Collection
     */
    public function getMedia($tags, bool $matchAll = false): Collection;

    public function getMediaMatchAll(array $tags): Collection;

    /**
     * @param string|string[] $tags
     * @param bool $matchAll
     * @return Media|null
     */
    public function firstMedia($tags, bool $matchAll = false): ?Media;

    /**
     * @param string|string[] $tags
     * @param bool $matchAll
     * @return Media|null
     */
    public function lastMedia($tags, bool $matchAll = false): ?Media;

    public function getAllMediaByTag(): Collection;

    public function getTagsForMedia(Media $media): array;

    /**
     * @param array|string $relations
     * @return mixed
     */
    public function load($relations);

    /**
     * @param array $models
     * @return MediableCollection
     */
    public function newCollection(array $models = []);
}
