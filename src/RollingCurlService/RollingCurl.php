<?php


namespace RollingCurlService;


class RollingCurl
{
    private $requests; // Array of RollingCurlRequests
    private $requestMap; // Requests added to curl multi and ready to be handled
    private $rollingWindow = 5; // Max number of parallel requests
    private $multiHandler;
    private $options = [ // Base curl opts
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'ROLLING_CURL',
    ];

    /**
     * @param array $options
     */
    public function setOptions(Array $options, bool $addToDefaultOptions = false): void // Set global options
    {
        $this->options = $addToDefaultOptions ? $options + $this->options : $options;
    }

    /**
     * @param RollingCurlRequest $request
     */
    public function addRequest(RollingCurlRequest $request): void
    {
        $this->requests[] = $request;
    }

    /**
     * @param int $rollingWindow
     */
    public function setRollingWindow(int $rollingWindow): void
    {
        $this->rollingWindow = $rollingWindow;
    }

    /**
     * @param RollingCurlRequest $request
     *
     * @return array
     */
    public function getRequestOptions(RollingCurlRequest $request): array
    {
        // Merge request options with global options or replace options
        if ($request->getAddToGlobalOptions()) {
            $options = $request->getOptions() ? $request->getOptions() + $this->options : $this->options;
        } else {
            $options = $request->getOptions() ?: $this->options;
        }

        $options[CURLOPT_URL] = $request->getUrl();

        return $options;
    }


    public function clear(): void // Resets multicurl
    {
        $this->requests = [];
        $this->requestMap = [];
        $this->rollingWindow = 5;
    }

    /**
     * @param null $callback
     *
     * @return array|bool
     */
    public function execute($callback = null)
    {
        if ( ! $this->requests) {
            return [];
        }
        // rolling curl window must always be greater than 1
        return count($this->requests) === 1 ? $this->singleCurl($callback) : $this->rollCurl($callback);
    }

    /**
     * @param $callback
     *
     * @return bool
     */
    private function rollCurl($callback): bool
    {
        $this->multiHandler = curl_multi_init();

        // Smallest of the two values
        $window = min([$this->rollingWindow, count($this->requests)]);

        // start the first batch of requests
        for ($requestIndex = 0; $requestIndex < $window; $requestIndex++) {
            $this->initCurlRequest($requestIndex);
        }

        do {
            $status = curl_multi_exec($this->multiHandler, $running);
            curl_multi_select($this->multiHandler);

            // a request was just completed -- find out which one
            while ($transfer = curl_multi_info_read($this->multiHandler)) {

                $info = curl_getinfo($transfer['handle']);
                $key = (int)$transfer['handle']; // Get current transfer handle
                $request = $this->requests[$this->requestMap[$key]];
                unset($this->requestMap[$key]);

                if (isset($this->requests[$requestIndex])) {
                    // start a new request
                    $this->initCurlRequest($requestIndex);
                }

                // remove curl handle that just completed
                curl_multi_remove_handle($this->multiHandler, $transfer['handle']);

                if (is_callable($callback)) { // Handler in callback
                    if ($request->getFileToWrite() === null) {
                        $output = curl_multi_getcontent($transfer['handle']);
                        $callback($output, $info, $request);

                    } else { // Result was written to file - no result to handle
                        $output = ['A file write path was provided => output was written to file'];
                        $callback($output, $info, $request);
                    }
                }
                // increment request index
                $requestIndex++;

                $status = curl_multi_exec($this->multiHandler, $running);
            }

        } while ($running > 0 && $status === CURLM_OK);

        curl_multi_close($this->multiHandler);

        return true;
    }

    /**
     * @param int $RequestIndex
     */
    private function initCurlRequest(int $RequestIndex): void
    {
        $ch      = curl_init();
        $options = $this->getRequestOptions($this->requests[$RequestIndex]);
        // If file provided write result to file
        if ($path = $this->requests[$RequestIndex]->getFileToWrite()) {
            $options[CURLOPT_FILE] = fopen($path, 'wb');
        }
        curl_setopt_array($ch, $options);
        curl_multi_add_handle($this->multiHandler, $ch);

        // Add to our request Maps
        $this->requestMap[(string)$ch] = $RequestIndex;
    }


    /**
     * @param $callBack
     *
     * @return array|bool
     */
    private function singleCurl($callBack)
    {
        $ch      = curl_init();
        $request = array_shift($this->requests);
        $options = $this->getRequestOptions($request);
        if ($path = $request->getFileToWrite()) {
            $options[CURLOPT_FILE] = fopen($path, 'wb');
        }
        curl_setopt_array($ch, $options);
        $output = curl_exec($ch);
        $info   = curl_getinfo($ch);
        // it's not neccesary to set a callback for one-off requests
        if ($callBack) {
            if (is_callable($callBack)) {
                $callBack($output, $info, $request);
            }
        } else {
            return ['info' => $info, 'output' => $output];
        }

        return true;
    }

}















