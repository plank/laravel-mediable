<?php

namespace Plank\Mediable\SourceAdapters;

use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;

/**
 * Source Adapter Factory.
 *
 * Generates SourceAdapter instances for different sources
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class SourceAdapterFactory
{
    /**
     * Map of which adapters to use for a given source class.
     * @var array
     */
    private $class_adapters = [];

    /**
     * Map of which adapters to use for a given stream wrapper.
     * @var array
     */
    private $stream_adapters = [];

    /**
     * Map of which adapters to use for a given string pattern.
     * @var array
     */
    private $pattern_adapters = [];

    /**
     * Create a Source Adapter for the provided source.
     * @param  object|string $source
     * @return \Plank\Mediable\SourceAdapters\SourceAdapterInterface
     * @throws \Plank\Mediable\Exceptions\MediaUpload\ConfigurationException If the provided source does not match any of the mapped classes or patterns
     */
    public function create($source)
    {
        $adapter = null;

        if ($source instanceof SourceAdapterInterface) {
            return $source;
        } elseif (is_object($source)) {
            $adapter = $this->adaptClass($source);
        } elseif (is_resource($source)) {
            $adapter = $this->adaptResource($source);
        } elseif (is_string($source)) {
            $adapter = $this->adaptString($source);
        }

        if ($adapter) {
            return new $adapter($source);
        }

        throw ConfigurationException::unrecognizedSource($source);
    }

    /**
     * Specify the FQCN of a SourceAdapter class to use when the source inherits from a given class.
     * @param string $adapter_class
     * @param string $source_class
     * @return void
     */
    public function setAdapterForClass($adapter_class, $source_class)
    {
        $this->validateAdapterClass($adapter_class);
        $this->class_adapters[$source_class] = $adapter_class;
    }

    /**
     * Specify the FQCN of a SourceAdapter class to use when the source is a stream resource implementing a given stream wrapper.
     * @param string $adapter_class
     * @param string $source_wrapper
     * @return void
     */
    public function setAdapterForStream($adapter_class, $source_wrapper)
    {
        $this->validateAdapterClass($adapter_class);
        $this->stream_adapters[$source_wrapper] = $adapter_class;
    }

    /**
     * Specify the FQCN of a SourceAdapter class to use when the source is a string matching the given pattern.
     * @param string $adapter_class
     * @param string $source_class
     * @return void
     */
    public function setAdapterForPattern($adapter_class, $source_pattern)
    {
        $this->validateAdapterClass($adapter_class);
        $this->pattern_adapters[$source_pattern] = $adapter_class;
    }

    /**
     * Choose an adapter class for the class of the provided object.
     * @param  object $source
     * @return \Plank\Mediable\SourceAdapters\SourceAdapterInterface|null
     */
    private function adaptClass($source)
    {
        $tree = class_parents($source);
        array_unshift($tree, get_class($source));
        foreach ($this->class_adapters as $class => $adapter) {
            if (in_array($class, $tree)) {
                return $adapter;
            }
        }
    }

    /**
     * Choose an adapter class for the provided resource.
     * @param  resource $source
     * @return \Plank\Mediable\SourceAdapters\SourceAdapterInterface|null
     */
    private function adaptResource($source)
    {
        $type = get_resource_type($source);

        if ($type === 'stream') {
            return $this->adaptStream($source);
        }
    }

    /**
     * Choose an adapter class for the provided stream resource.
     * @param  resource $source
     * @return \Plank\Mediable\SourceAdapters\SourceAdapterInterface|null
     */
    private function adaptStream($source)
    {
        $metadata = stream_get_meta_data($source);

        foreach ($this->stream_adapters as $wrapper => $class) {
            if ($metadata['wrapper_type'] === $wrapper) {
                return $class;
            }
        }
    }

    /**
     * Choose an adapter class for the provided string.
     * @param  string $source
     * @return \Plank\Mediable\SourceAdapters\SourceAdapterInterface|null
     */
    private function adaptString($source)
    {
        foreach ($this->pattern_adapters as $pattern => $adapter) {
            $pattern = '/'.str_replace('/', '\\/', $pattern).'/i';
            if (preg_match($pattern, $source)) {
                return $adapter;
            }
        }
    }

    /**
     * Verify that the provided class implements the SourceAdapter interface.
     * @param  string $class
     * @throws \Plank\Mediable\Exceptions\MediaUpload\ConfigurationException If class is not valid
     * @return void
     */
    private function validateAdapterClass($class)
    {
        if (! class_implements($class, SourceAdapterInterface::class)) {
            throw ConfigurationException::cannotSetAdapter($class);
        }
    }
}
