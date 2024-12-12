<?php


namespace RollingCurlService;


class RollingCurlRequest
{
    private string $url;
    private ?array $options;
    private ?array $attributes;
    private bool $addToGlobalOptions = false;
    private ?string $fileToWrite;


    public function __construct(string $url, ?Array $options = null, ?Array $attributes = null)
    {
        $this->url        = $url;
        $this->options    = $options;
        $this->attributes = $attributes;
    }

    /**
     * An array of cURL options, can replace or be added to global options.
     *
     * @param array $options
     * @param bool $addToGlobalOptions
     */
    public function setOptions(array $options, bool $addToGlobalOptions = false): void
    {
        $this->options = $options;
        $this->addToGlobalOptions = (bool)$addToGlobalOptions;
    }

    /**
     * Attributes is an array of custom data accessible at any point, before requests are executed and in callback
     * when a response has been received. Could contain e.g. an ID, an object...
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }


    /**
     * Keep global cURL options but add options specific to a request.
     *
     * @param bool $addToGlobalOptions
     */
    public function setAddToGlobalOptions(bool $addToGlobalOptions): void
    {
        $this->addToGlobalOptions = $addToGlobalOptions;
    }

    /**
     * Write the request output to file.
     * If a file path is provided the response will be written to file and not returned in the output.
     *
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