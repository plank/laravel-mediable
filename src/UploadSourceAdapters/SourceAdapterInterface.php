<?php

namespace Frasmage\Mediable\UploadSourceAdapters;

Interface SourceAdapterInterface{
	abstract public function path();
	abstract public function filename();
	abstract public function extension();
	abstract public function mimeType();
	abstract public function contents();
	abstract public function valid();
	abstract public function size();
}
