<?php

namespace Frasmage\Mediable\UrlGenerators;

use Frasmage\Mediable\Exceptions\MediaUrlException;
use Frasmage\Mediable\Media;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Routing\UrlGenerator as Url;

class LocalUrlGenerator extends BaseUrlGenerator{

    protected $url;

    public function __construct(Config $config, Url $url){
        parent::__construct($config);
        $this->url = $url;
    }

    public function isPubliclyAccessible(){
        return strpos($this->getAbsolutePath(), public_path()) === 0;
    }

    /**
     * Get the path to relative to the webroot
     * @throws MediaUrlException If media's disk is not publicly accessible
     * @return string
     */
    public function getPublicPath()
    {
        if (!$this->isPubliclyAccessible()) {
            throw MediaUrlException::mediaNotPubliclyAccessible($this->getAbsolutePath(), public_path());
        }
        $path = str_replace(public_path(), '', $this->getAbsolutePath());

        return $this->cleanDirectorySeparators($path);
    }

    /**
     * Get the url to a file
     * @return
     */
    public function getUrl(){
        return $this->url->asset($this->getPublicPath());
    }

    /**
     * Get the absolute path to the file on the filesystem
     * @return string
     */
    public function getAbsolutePath(){
        return $this->diskRoot() . DIRECTORY_SEPARATOR . $this->media->getDiskPath();
    }


    /**
     * Correct directory separator slashes on non-unix systems
     * @param  string $path
     * @return string
     */
    protected function cleanDirectorySeparators($path){
        if (DIRECTORY_SEPARATOR != '/') {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }
        return $path;
    }

    /**
     * Get the absolute path to the root of the storage disk
     * @return string
     */
    private function diskRoot()
    {
        return $this->getDiskConfig('root');
    }

}
