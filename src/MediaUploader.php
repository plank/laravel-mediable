<?php

namespace Frasmage\Mediable;

use Frasmage\Mediable\Excpetions\MediaUploadException;
use Frasmage\Mediable\Excpetions\MediaManagerException;
use Frasmage\Mediable\Media;
use Frasmage\Mediable\UploadSourceAdapters\SourceAdapterFactory;
use Illuminate\Filesystem\FileSystemManager;
use Storage;

class MediaUploader{

    protected $filesystem;
    protected $factory;

    public function __construct(FileSystemManager $filesystem, SourceAdapterFactory $factory){
        $this->filesystem = $filesystem;
        $this->factory = $factory;
    }

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

	protected function sanitizePath($path){
		return str_replace(['#', '?', '\\'], '-', $path);
	}

	protected function sanitizeFileName($file){
		return str_replace(['#', '?', '\\', '/'], '-', $file);
	}

	protected function inferMediaType($mime, $ext){
		$type_mime = $this->inferMediaTypeFromMime($mime);
		$type_ext = $this->inferMediaTypeFromExtension($ext);

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

		throw MediaUploadException::unrecognizedFileType($mime, $ext);
	}


	protected function inferMediaTypeFromMime($mime){
		foreach(config('admin.media.type_map.mimes') as $type => $mimes){
			if(in_array($mime, $mimes)){
				return $type;
			}
		}
	}

	protected function inferMediaTypeFromExtension($ext){
		foreach(config('admin.media.type_map.extensions') as $type => $extensions){
			if(in_array($ext, $extensions)){
				return $type;
			}
		}
	}

	protected function mediaTypeIsAllowed($type, $allowed_types){
		if(!empty($allowed_types) && !in_array($type, $allowed_types)){
			throw MediaUploadException::typeRestricted($type, $allowed_types);
		}
	}

	protected function verifyFileSize($size){
		$max = config('admin.media.max_size');
        if($max > 0 && $size > $max){
			throw MediaUploadException::fileIsTooBig($size);
		}
	}

	protected function generateUniqueFilename($storage, $path, $name, $ext){
		$counter = 0;
		$new_name = $name;
		$filepath = "{$path}/{$new_name}.{$ext}";
		while($storage->has($filepath)){
			$counter++;
			$new_name = "{$name} ({$counter})";
			$filepath = "{$path}/{$new_name}.{$ext}";
		}
		return $new_name;
	}


}
