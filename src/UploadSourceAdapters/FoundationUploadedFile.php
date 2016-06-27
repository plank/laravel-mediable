<?php

namespace Frasmage\Mediable\UploadSourceAdapters;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FoundationUploadedFile implements SourceAdapterInterface{

    protected $source;

	public function __construct(UploadedFile $source){
		$this->source = $source;
	}

	public function path(){
		return $this->source->getPath().'/'.$this->source->getFilename();
	}

	public function filename(){
		return pathinfo($this->source->getClientOriginalName(), PATHINFO_FILENAME);
	}

	public function extension(){
		return $this->source->getClientOriginalExtension();
	}

	public function mimeType(){
		return $this->source->getClientMimeType();
	}

	public function contents(){
		return fopen($this->path(), 'r');
	}

	public function valid(){
		return $this->source->isValid();
	}

	public function size(){
		return $this->source->getClientSize();
	}
}
