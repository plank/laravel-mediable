<?php
declare(strict_types=1);

namespace Plank\Mediable\UrlGenerators;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Routing\UrlGenerator;
use Plank\Mediable\Exceptions\MediaUrlException;

class LocalUrlGenerator extends BaseUrlGenerator
{
    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * Constructor.
     * @param Config $config
     * @param UrlGenerator $url
     */
    public function __construct(Config $config, UrlGenerator $url)
    {
        parent::__construct($config);
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function isPubliclyAccessible(): bool
    {
        return (parent::isPubliclyAccessible() || $this->isInWebroot()) && $this->media->isVisible();
    }

    /**
     * Get the path to relative to the webroot.
     * @return string
     * @throws MediaUrlException If media's disk is not publicly accessible
     */
    public function getPublicPath(): string
    {
        if (!$this->isPubliclyAccessible()) {
            throw MediaUrlException::mediaNotPubliclyAccessible($this->getAbsolutePath());
        }
        if ($this->isInWebroot()) {
            $path = str_replace(public_path(), '', $this->getAbsolutePath());
        } else {
            $path = rtrim($this->getPrefix(), '/') . '/' . $this->media->getDiskPath();
        }

        return $this->cleanDirectorySeparators($path);
    }

    /**
     * {@inheritdoc}
     * @throws \Plank\Mediable\Exceptions\MediaUrlException If media's disk is not publicly accessible
     */
    public function getUrl(): string
    {
        $path = $this->getPublicPath();

        $url = $this->getDiskConfig('url');

        if ($url) {
            if ($this->isInWebroot()) {
                $path = $this->media->getDiskPath();
            }

            return rtrim($url, '/') . '/' . trim($path, '/');
        }

        return $this->url->asset($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getAbsolutePath(): string
    {
        return $this->getDiskConfig('root') . DIRECTORY_SEPARATOR . $this->media->getDiskPath();
    }

    /**
     * Correct directory separator slashes on non-unix systems.
     * @param  string $path
     * @return string
     */
    protected function cleanDirectorySeparators(string $path): string
    {
        if (DIRECTORY_SEPARATOR != '/') {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }

        return $path;
    }

    private function isInWebroot(): bool
    {
        return strpos($this->getAbsolutePath(), public_path()) === 0;
    }

    /**
     * Get the prefix.
     *
     * If the prefix and the url are not set, we will assume the prefix
     * is "storage", in order to point to the default symbolink link.
     *
     * Otherwise, we will trust the user has correctly set the prefix and/or the url.
     *
     * @return string
     */
    private function getPrefix()
    {
        $prefix = $this->getDiskConfig('prefix', '');
        $url = $this->getDiskConfig('url');

        if (!$prefix && !$url) {
            return 'storage';
        }

        return $prefix;
    }
}
