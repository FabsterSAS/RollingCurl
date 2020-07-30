<?php


namespace RollingCurlService;


class RollingCurlRequest
{
    private $url;
    private $options;
    private $attributes;
    private $addToGlobalOptions = false;
    private $writeToFile;

    function __construct(string $url, Array $options = null, Array $attributes = null)
    {
        $this->url        = $url;
        $this->options    = $options;
        $this->attributes = $attributes;
    }


    /**
     * @param array $options
     * @param bool $addToGlobalOptions
     */
    public function setOptions(array $options, bool $addToGlobalOptions = false): void
    {
        $this->options = $options;
        $this->addToGlobalOptions = $addToGlobalOptions ? true : false;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @param $addOptions
     */
    public function setAddToGlobalOptions(bool $addToGlobalOptions): void
    {
        $this->addToGlobalOptions = $addToGlobalOptions;
    }

    /**
     * @param string $writeToFile
     */
    public function setWriteToFile(string $writeToFile): void
    {
        $this->writeToFile = $writeToFile;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    public function getAddToGlobalOptions(): bool
    {
        return $this->addToGlobalOptions;
    }

    public function getWriteToFile(): ?string
    {
        return $this->writeToFile;
    }



}