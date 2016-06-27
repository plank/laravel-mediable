<?php

namespace Frasmage\Mediable\UploadSourceAdapters;

use Symfony\Component\HttpFoundation\File\File;

class FoundationFile implements SourceAdapterInterface{

	protected $source;

	public function __construct(File $source){
		$this->source = $source;
	}

	public function path(){
		return $this->source->getPath().'/'.$this->source->getFilename();
	}

	public function filename(){
		return pathinfo($this->source->getFilename(), PATHINFO_FILENAME);
	}

	public function extension(){
		return pathinfo($this->source->getFilename(), PATHINFO_EXTENSION);
	}

	public function mimeType(){
		return $this->source->getMimeType();
	}

	public function contents(){
		return fopen($this->path(), 'r');
	}

	public function valid(){
		return file_exists($this->path());
	}

	public function size(){
		return filesize($this->path());
	}
}
