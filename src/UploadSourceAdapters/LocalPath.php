<?php

namespace Frasmage\Mediable\UploadSourceAdapters;

class LocalPath implements SourceAdapterInterface{

    protected $source;

    public function __construct($source){
        $this->source = $source;
    }

	public function path(){
		return $this->source;
	}

	public function filename(){
		return pathinfo($this->source, PATHINFO_FILENAME);
	}

	public function extension(){
		return pathinfo($this->source, PATHINFO_EXTENSION);
	}

	public function mimeType(){
		return mime_content_type($this->source);
	}

	public function contents(){
		return fopen($this->source, 'r');
	}

	public function valid(){
		return file_exists($this->source);
	}

	public function size(){
		return filesize($this->source);
	}
}
