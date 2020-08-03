<?php


namespace RollingCurlService;


class RollingCurlRequest
{
    private $url;
    private $options;
    private $attributes;
    private $addToGlobalOptions = false;
    private $fileToWrite;


    public function __construct(string $url, Array $options = null, Array $attributes = null)
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
     * @param bool $addToGlobalOptions
     */
    public function setAddToGlobalOptions(bool $addToGlobalOptions): void
    {
        $this->addToGlobalOptions = $addToGlobalOptions;
    }

    /**
     * @param string $fileToWrite
     */
    public function setFileToWrite(string $fileToWrite): void
    {
        $this->fileToWrite = $fileToWrite;
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

    /**
     * @return bool
     */
    public function getAddToGlobalOptions(): bool
    {
        return $this->addToGlobalOptions;
    }

    public function getFileToWrite(): ?string
    {
        return $this->fileToWrite;
    }



}