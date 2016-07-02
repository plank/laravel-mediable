<?php

namespace Frasmage\Mediable\SourceAdapters;

Interface SourceAdapterInterface{

    /**
     * Get the absolute path to the file
     * @return string
     */
	public function path();

    /**
     * Get the name of the file
     * @return string
     */
	public function filename();

    /**
     * Get the extension of the file
     * @return string
     */
	public function extension();

    /**
     * Get the MIME type of the file
     * @return string
     */
	public function mimeType();

    /**
     * Get the body of the file
     * @return string
     */
	public function contents();

    /**
     * Check if the file can be transfered
     * @return Boolean
     */
	public function valid();

    /**
     * Determine the size of the file
     * @return integer
     */
	public function size();
}
