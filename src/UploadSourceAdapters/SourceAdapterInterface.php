<?php

namespace Frasmage\Mediable\UploadSourceAdapters;

Interface SourceAdapterInterface{

    /**
     * Get the absolute path to the file
     * @return string
     */
	abstract public function path();

    /**
     * Get the name of the file
     * @return string
     */
	abstract public function filename();

    /**
     * Get the extension of the file
     * @return string
     */
	abstract public function extension();

    /**
     * Get the MIME type of the file
     * @return string
     */
	abstract public function mimeType();

    /**
     * Get the body of the file
     * @return string
     */
	abstract public function contents();

    /**
     * Check if the file can be transfered
     * @return Boolean
     */
	abstract public function valid();

    /**
     * Determine the size of the file
     * @return integer
     */
	abstract public function size();
}
