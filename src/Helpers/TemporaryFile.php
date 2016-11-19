<?php

namespace Plank\Mediable\Helpers;

use Symfony\Component\HttpFoundation\File\File;

class TemporaryFile extends File
{
    /**
     * The original name of the temporary file.
     *
     * @var string
     */
    protected $originalName;

    /**
     * Creates a temporary file with the given contents.
     *
     * @param mixed  $contents     The contents to be written on the file
     * @param string $originalName The original file name
     * @param bool   $checkPath    Whether to check the path or not
     */
    public function __construct($contents, $originalName, $checkPath = true)
    {
        $this->originalName = $originalName;

        if (! empty($contents)) {
            $path = tempnam(sys_get_temp_dir(), $this->getOriginalName());
        } else {
            $path = '';
        }

        $this->put($path, $contents);

        parent::__construct($path, $checkPath);
    }

    /**
     * Delete the file when the script ends.
     *
     */
    public function __destruct()
    {
        $this->delete();
    }

    /**
     * Returns the original file name.
     *
     * @return string|null The original name
     */
    public function getOriginalName()
    {
        return pathinfo($this->originalName, PATHINFO_FILENAME);
    }

    /**
     * Returns the original file extension.
     *
     * @return string The extension
     */
    public function getOriginalExtension()
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * Open the file and return the resource handle.
     *
     * @param  string $mode
     * @return resource
     */
    public function open($mode = 'r')
    {
        return fopen($this->getRealPath(), $mode);
    }

    /**
     * Write the contents to the file.
     *
     * @param  mixed $contents
     * @return int|bool
     */
    public function put($path, $contents)
    {
        if (is_writable($path)) {
            return file_put_contents($path, $contents);
        }

        return false;
    }

    /**
     * Delete the temporaty file.
     *
     * @return void
     */
    public function delete()
    {
        if ($this->isFile()) {
            return unlink($this->getRealPath());
        }

        return false;
    }
}
