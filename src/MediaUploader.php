<?php

namespace Frasmage\Mediable;

use Frasmage\Mediable\Excpetions\MediaUploadException;
use Frasmage\Mediable\Excpetions\MediaManagerException;
use Frasmage\Mediable\Media;
use Frasmage\Mediable\UploadSourceAdapters\SourceAdapterFactory;
use Illuminate\Filesystem\FileSystemManager;
use Storage;

class MediaUploader{
    /**
     * @var FileSystemManager
     */
    protected $filesystem;

    /**
     * @var SourceAdapterFactory
     */
    protected $factory;

    /**
     * Constructor
     * @param FileSystemManager    $filesystem
     * @param SourceAdapterFactory $factory
     */
    public function __construct(FileSystemManager $filesystem, SourceAdapterFactory $factory){
        $this->filesystem = $filesystem;
        $this->factory = $factory;
    }

    /**
     * Upload a file to a disk and create a media model to represent it
     * @param  string|object $source
     * A representation of the file, must be an object of a registered class or a string matching a registered pattern of `SourceAdapterFactory`.
     * By default, absolute paths, urls, File and UploadedFile are recognized.
     * @param  string $disk
     * The identifier of the filesystem disk to store the media on
     * @param  string $destination
     * The directory path at to store the file at, relative to the filesystem disk's root
     * @param  string $name
     * Filename to use for the file. defaults to the name of the source.
     * @param  array  $allowed_types
     * Restrict uploading to specific type(s) of files
     * @return Media
     * @throws MediaUploadException If the file does not pass a security check
     */
	public function upload($source, $disk, $destination, $name = null, $allowed_types = []){
		$source = $this->factory->create($source);

		if(!$source->valid()){
			throw MediaUploadException::fileNotFound($source->path());
		}

		if(!$name){
			$name = $source->filename();
		}
		$destination = trim($this->sanitizePath($destination), '/');
		$storage = $this->filesystem->disk($disk);
		$extension = $source->extension();
		$name = $this->generateUniqueFilename($storage, $destination, $this->sanitizeFileName($name), $extension);

		$class = config('mediable.model');
		$media = new $class;

		$media->directory = $destination;
		$media->basename = $name . '.' . $extension;
		$media->disk = $disk;
		$media->mime = $source->mimeType();
		$media->type = $this->inferMediaType($media->mime, $extension);

		$this->mediaTypeIsAllowed($media->type, (array)$allowed_types);
		$this->verifyFileSize($source->size());

		$storage->put($media->diskPath(), $source->contents());
		$media->size = $storage->size($media->diskPath());

		$media->save();

		return $media;
	}

    /**
     * Remove any disallowed characters from a directory value
     * @param  string $path
     * @return string
     */
	protected function sanitizePath($path){
		return str_replace(['#', '?', '\\'], '-', $path);
	}

    /**
     * Remove any disallowed characters from a filename
     * @param  string $file [description]
     * @return string       [description]
     */
	protected function sanitizeFileName($file){
		return str_replace(['#', '?', '\\', '/'], '-', $file);
	}

    /**
     * Determine the media type of the file based on the MIME type and the extension
     * @param  string $mime
     * @param  string $extension
     * @return string
     */
	protected function inferMediaType($mime, $extension){
		$type_mime = $this->inferMediaTypeFromMime($mime);
		$type_ext = $this->inferMediaTypeFromExtension($extension);

		if(config('admin.media.strict_type_checking')){
			if(($type_mime || $type_ext) && $type_mime != $type_ext){
				throw MediaUploadException::strictTypeMismatch($mime, $extension);
			}
		}

		if($type_mime){
			return $type_mime;
		}

		if($type_ext){
			return $type_ext;
		}

		if(config('admin.media.allow_unrecognized_types')){
			return Media::TYPE_OTHER;
		}

		throw MediaUploadException::unrecognizedFileType($mime, $extension);
	}

    /**
     * Determine the media type of the file based on the MIME type
     * @param  string $mime
     * @return string
     */
	protected function inferMediaTypeFromMime($mime){
		foreach(config('admin.media.type_map.mimes') as $type => $mimes){
			if(in_array($mime, $mimes)){
				return $type;
			}
		}
	}

    /**
     * Determine the media type of the file based on the extension
     * @param  string $extension
     * @return string
     */
	protected function inferMediaTypeFromExtension($extension){
		foreach(config('admin.media.type_map.extensions') as $type => $extensions){
			if(in_array($extension, $extensions)){
				return $type;
			}
		}
	}

    /**
     * Verify that the uploaded file's is one that is allowed
     * @param  string $type
     * @param  string[] $allowed_types
     * @return void
     */
	protected function mediaTypeIsAllowed($type, $allowed_types){
		if(!empty($allowed_types) && !in_array($type, $allowed_types)){
			throw MediaUploadException::typeRestricted($type, $allowed_types);
		}
	}

    /**
     * Verify that the file being uploaded is not larger than the maximum
     * @param  integer $size
     * @throws MediaUploadException If the file is too large
     * @return void
     */
	protected function verifyFileSize($size){
		$max = config('admin.media.max_size');
        if($max > 0 && $size > $max){
			throw MediaUploadException::fileIsTooBig($size);
		}
	}

	/**
     * Increment file name until one is found that doesn't already exist
     * @param  Filesystem $storage
     * @param  string $directory
     * @param  string $name
     * @param  string $extension
     * @return string
     */
    protected function generateUniqueFilename($storage, $directory, $name, $extension){
        $counter = 0;
        $new_name = $name;
        $filepath = "{$directory}/{$new_name}.{$extension}";
        while($storage->has($filepath)){
            $counter++;
            $new_name = "{$name} ({$counter})";
            $filepath = "{$directory}/{$new_name}.{$extension}";
        }
        return $new_name;
    }


}
