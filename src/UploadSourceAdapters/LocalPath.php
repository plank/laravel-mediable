<?php

namespace Frasmage\Mediable\UploadSourceAdapters;

class LocalPath implements SourceAdapterInterface{

    /**
     * The source string
     * @var string
     */
    protected $source;

    /**
     * Constructor
     * @param string $source
     */
    public function __construct($source){
        $this->source = $source;
    }

    /**
     * {@inheritDoc}
     */
	public function path(){
		return $this->source;
	}

    /**
     * {@inheritDoc}
     */
	public function filename(){
		return pathinfo($this->source, PATHINFO_FILENAME);
	}

    /**
     * {@inheritDoc}
     */
	public function extension(){
		return pathinfo($this->source, PATHINFO_EXTENSION);
	}

    /**
     * {@inheritDoc}
     */
	public function mimeType(){
		return mime_content_type($this->source);
	}

    /**
     * {@inheritDoc}
     */
	public function contents(){
		return fopen($this->source, 'r');
	}

    /**
     * {@inheritDoc}
     */
	public function valid(){
		return file_exists($this->source);
	}

    /**
     * {@inheritDoc}
     */
	public function size(){
		return filesize($this->source);
	}
}
