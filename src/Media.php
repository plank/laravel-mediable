<?php

namespace Frasmage\Mediable;

use Frasmage\Mediable\Exceptions\MediaUrlException;
use Frasmage\Mediable\Exceptions\MediaMoveException;
use Glide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Storage;

class Media extends Model
{

    const TYPE_IMAGE = 'image';
    const TYPE_IMAGE_VECTOR = 'vector';
    const TYPE_PDF = 'pdf';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_ARCHIVE = 'archive';
    const TYPE_DOCUMENT = 'document';
    const TYPE_OTHER = 'other';
    const TYPE_ALL = 'all';

    protected $guarded = ['id', 'disk', 'directory', 'filename', 'extension' 'size', 'mime', 'type'];

    /**
     * {@inheritDoc}
     */
    public static function boot()
    {
        parent::boot();

        //remove file on deletion
        static::deleted(function ($media) {
            $media->filesystem()->delete($media->diskPath());
        });
    }

    /**
     * Retrieve all associated models of given class
     * @param  string $class FQCN
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function models($class)
    {
        $this->morphedByMany($class, 'mediable')->withPivot('association');
    }

    /**
     * Get the absolute path to the parent directory of the file
     * @return string
     */
    public function getDirnameAttribute()
    {
        return pathinfo($this->absolutePath(), PATHINFO_DIRNAME);
    }

    /**
     * Retrieve the file extension
     * @return string
     */
    public function getBasenameAttribute()
    {
        return $this->filename . '.' $this->extension;
    }

    /**
     * Query scope for to find media in a particular directory
     * @param  Builder  $q
     * @param  string  $disk      Filesystem disk to search in
     * @param  string  $directory Path relative to disk
     * @param  boolean $recursive (_optional_) If true, will find media in or under the specified directory
     */
    public function scopeInDirectory(Builder $q, $disk, $directory, $recursive = false)
    {
        $q->where('disk', $disk);
        if ($recursive) {
            $directory = str_replace(['%', '_'], ['\%', '\_'], $directory);
            $q->where('directory', 'like', $directory.'%');
        } else {
            $q->where('directory', '=', $directory);
        }
    }

    /**
     * Query scope for to find media in a particular directory or one of its subdirectories
     * @param  Builder  $q
     * @param  string  $disk      Filesystem disk to search in
     * @param  string  $directory Path relative to disk
     */
    public function scopeInOrUnderDirectory(Builder $q, $disk, $directory)
    {
        $q->inDirectory($disk, $directory, true);
    }

    public function scopeWhereBasename($basename)
    {
        $q->where('filename', pathinfo($basename, PATHINFO_FILENAME))
            ->where('extension', pathinfo($basename), PATHINFO_EXTENSION);
    }

    public function scopeForPathOnDisk($disk, $path)
    {
        $q->where('disk', $disk)
            ->where('directory', pathinfo($path, PATHINFO_DIRNAME))
            ->where('filename', pathinfo($path, PATHINFO_FILENAME))
            ->where('extension', pathinfo($path), PATHINFO_EXTENSION);
    }

    /**
     * Calculate the file size in human readable byte notation
     * @param  integer $precision (_optional_) Number of decimal places to include.
     * @return string
     */
    public function readableSize($precision = 1)
    {
        if ($this->size === 0) {
            return '0 bytes';
        }
        $units = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $exponent = floor(log($this->size, 1024));
        $value = $this->size / pow(1024, $exponent);
        return round($value, $precision) . ' ' . $units[$exponent];
    }

    /**
     * Get the absolute filesystem path to the file
     * @return string
     */
    public function absolutePath()
    {
        return $this->diskRoot() . '/' . $this->diskPath();
    }

    /**
     * Get the path to the file relative to the root of the disk
     * @return string
     */
    public function diskPath()
    {
        return trim(trim($this->directory, '/') .'/' . $this->basename, '/');
    }

    /**
     * Check if the file is located below the public webroot
     * @return boolean
     */
    public function isPubliclyAccessible()
    {
        return strpos($this->absolutePath(), public_path()) == 0;
    }

    /**
     * Get the path to relative to the webroot
     * @throws MediaUrlException If media's disk is not publicly accessible
     * @return string
     */
    public function publicPath()
    {
        if (!$this->isPubliclyAccessible()) {
            throw MediaUrlException::mediaNotPubliclyAccessible($this->diskRoot(), public_path());
        }
        $path = str_replace(public_path(), '', $this->diskRoot()) . '/' . $this->diskPath();

        if (DIRECTORY_SEPARATOR != '/') {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }
        return $path;
    }

    /**
     * Get the absolute URL to the media file
     * @throws MediaUrlException If media's disk is not publicly accessible
     * @return string
     */
    public function url($absolute = true)
    {
        return $absolute ? asset($this->publicPath()) : $this->publicPath();
    }

    /**
     * Check if the file is located below the glide root
     * @return boolean
     */
    public function isGlideAccesible()
    {
        return strpos($this->absolutePath(), $this->glideRoot()) == 0;
    }

    /**
     * Get the path relative to the glide root
     * @throws MediaUrlException If media's disk is not visible to glide
     * @return string
     */
    public function glidePath()
    {
        $glide_path = $this->glideRoot();
        if (! $this->isGlideAccesible()) {
            throw MediaUrlException::mediaNotGlideAccessible($this->absolutePath(), $glide_path);
        }
        $path = str_replace($glide_path, '', $this->diskRoot()) . '/' . $this->diskPath();

        if (DIRECTORY_SEPARATOR != '/') {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }
        return $path;
    }

    /**
     * Generate a Url for applying glide modifications
     * @throws MediaUrlException If media's disk is not accessible to glide
     * @return string
     */
    public function glideUrl($params = [])
    {
        $glide = app('laravel-glide-image')->load($this->glidePath(), $params);
        $glide->useAbsoluteSourceFilePath();
        return asset($glide->getUrl());
    }

    /**
     * Generate a URL to access the media file by id
     *
     * This allows access to the file regardless of its location on disk.
     * @throws MediaUrlException If media's disk is not publicly accessible
     * @return string
     */
    public function accessUrl()
    {
        if (!$this->isPubliclyAccessible()) {
            throw MediaUrlException::mediaNotPubliclyAccessible($this->diskRoot(), public_path());
        }
        return route('media.show', ['id' => $this->id, 'ext' => $this->extension]);
    }

    /**
     * Check if the file exists on disk
     * @return boolean
     */
    public function fileExists()
    {
        return $this->storage()->has($this->diskPath());
    }

    /**
     * Move the file to a new location on disk
     *
     * Will invoke the `save()` method on the model after the associated file has been moved to prevent synchronization errors
     * @param  string $destination directory relative to disk root
     * @param  string $name        filename. Do not include extension
     * @throws  MediaMoveException If attempting to change the file extension or a file with the same name already exists at the destination
     */
    public function move($destination, $filename = null)
    {
        if (!$filename) {
            $filename = $this->filename;
        }

        //remove extension from filename
        if(mb_strrpos($filename, '.' . $this->extension) === mb_strlen($filename) - mb_strlen($this->extension) -1){
            $filename = mb_substr(0, mb_strlen($filename) - mb_strlen($this->extension) -1);
        }

        $destination = trim($destination, '/');
        $target_path = $destination . '/' . $filename . '.' . $this->extension;

        if ($this->storage()->has($target_path)) {
            throw MediaMoveException::destinationExists($target_path);
        }

        $this->storage()->move($this->diskPath(), $target_path);
        $this->filename = $filename;
        $this->directory = $destination;
        $this->save();
    }

    /**
     * Rename the file in place
     * @param  string $name
     * @see Media::move()
     */
    public function rename($filename)
    {
        $this->move($this->directory, $filename);
    }

    /**
     * Retrieve the contents of the file
     * @return string
     */
    public function contents()
    {
        return $this->storage()->get($this->diskPath());
    }

    /**
     * Get the filesystem object for this media
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected function storage()
    {
        return app('filesystem')->disk($this->disk);
    }

    /**
     * Get the absolute path to the root of the storage disk
     * @return string
     */
    private function diskRoot()
    {
        return config("filesystems.disks.{$this->disk}.root");
    }

    /**
     * Get the absolute path to the root of the storage disk
     * @return string
     */
    private function glideRoot()
    {
        return config('laravel-glide.source.path');
    }
}
