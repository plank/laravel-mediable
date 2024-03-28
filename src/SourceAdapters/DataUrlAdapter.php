<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;


/**
 * Raw content Adapter.
 *
 * Adapts a string representing raw contents.
 */
class DataUrlAdapter extends RawContentAdapter
{
    protected ?string $originalMime;

    protected string $dataUrl;

    /**
     * Constructor.
     * @param string $source
     */
    public function __construct(string $source)
    {
        $this->dataUrl = $source;
        if (preg_match(
                '/^data:(\w+\/[\.+\-\w]+(?:\w+=[^;]+;)*)?(;base64)?,/',
                $source,
                $matches
            ) === 0
        ) {
            throw new \InvalidArgumentException('Invalid Data URL format');
        }
        $this->originalMime = $matches[1] ?? null;
        $content = substr($source, strlen($matches[0]));
        $decodedSource = ($matches[2] ?? '') === ';base64'
            ? base64_decode($content)
            : rawurldecode($content);
        parent::__construct($decodedSource);
    }

    /**
     * {@inheritdoc}
     */
    public function getSource(): mixed
    {
        return $this->dataUrl;
    }
}
