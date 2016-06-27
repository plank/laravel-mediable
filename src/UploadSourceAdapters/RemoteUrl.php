<?php

namespace Frasmage\Mediable\UploadSourceAdapters;

class RemoteUrl implements SourceAdapterInterface{

	protected $headers;
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
		return $this->getHeader('Content-Type');
	}

	public function contents(){
		return fopen($this->source, 'r');
	}

	public function valid(){
		return strpos($this->getHeader(0), '200');
	}

	public function size(){
		return $this->getHeader('Content-Length');
	}

	protected function getHeader($key){
		if(!$this->headers){
			$this->headers = get_headers($this->source, 1);
		}
		if(array_key_exists($key, $this->headers)){
			return $this->headers[$key];
		}
		return null;
	}
}
