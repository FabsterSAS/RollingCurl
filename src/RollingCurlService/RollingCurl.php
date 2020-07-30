<?php


namespace RollingCurlService;


class RollingCurl
{
    public $requests; // Array of RollingCurlRequests
    public $requestMap; // Requests added to curl multi and ready to be handled
    public $rollingWindow = 5; // Max number of parallel requests
    public $options = [ // Base curl opts
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
        $this->options = $addToDefaultOptions ? $options + $this->options  : $options;
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
            $options = $request->getOptions() ? $request->getOptions() : $this->options;
        }

        $options[CURLOPT_URL] = $request->getUrl();

        return $options;
    }


    public function clear() // Resets multicurl
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
        // rolling curl window must always be greater than 1
        return count($this->requests) == 1 ? $this->singleCurl($callback) : $this->rollCurl($callback);
    }

    /**
     * @param $callback
     *
     * @return bool
     */
    private function rollCurl($callback)
    {
        $mh = curl_multi_init();

        // Smallest of the two values
        $window = min([$this->rollingWindow, count($this->requests)]);

        // start the first batch of requests
        for ($i = 0; $i < $window; $i++) {
            $ch = curl_init();
            $options = $this->getRequestOptions($this->requests[$i]);
            // If result should be written to file
            if ($path = $this->requests[$i]->getFileToWrite()) {
                $options[CURLOPT_FILE] = fopen($path, 'wb');
            }
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($mh, $ch);

            // Add to our request Maps
            $this->requestMap[(string)$ch] = $i;
        }

        do {
            $status = curl_multi_exec($mh, $running);
            curl_multi_select($mh);

            // a request was just completed -- find out which one
            while ($transfer = curl_multi_info_read($mh)) {

                $info = curl_getinfo($transfer['handle']);
                $key = (string)$transfer['handle']; // Get current transfer handle
                $request = $this->requests[$this->requestMap[$key]];
                unset($this->requestMap[$key]);

                // Handling OK result
                if ($info['http_code'] == 200) {

                    if (is_callable($callback)) { // Handler in callback

                        if ($request->getFileToWrite() == null) {
                            $output = curl_multi_getcontent($transfer['handle']);
                            call_user_func($callback, $output, $info, $request);

                        } else { // Result was written to file - no result to handle
                            $output = ['A file write path was provided => output was written to file'];
                            call_user_func($callback, $output, $info, $request);
                        }
                    }

                    if (isset($this->requests[$i])) {
                        // start a new request (it's important to do this before removing the old one)
                        $ch = curl_init();

                        //Add new url to rolling window
                        $options = $this->getRequestOptions($this->requests[$i]);

                        if ($path = $this->requests[$i]->getFileToWrite()) {
                            $options[CURLOPT_FILE] = fopen($path, 'wb');
                        }

                        curl_setopt_array($ch, $options);
                        curl_multi_add_handle($mh, $ch);
                        $this->requestMap[(string)$ch] = $i;
                    }

                    // increment request index
                    $i++;

                // Handling KO result
                } elseif ($info['http_code'] != 200) {

                    if (is_callable($callback)) {
                        $output = curl_multi_getcontent($transfer['handle']);
                        call_user_func($callback, $output, $info, $request);
                    }
                }

                // remove curl handle that just completed
                curl_multi_remove_handle($mh, $transfer['handle']);
                curl_close($transfer['handle']);
            }

        } while ($running > 0 && $status == CURLM_OK);

        curl_multi_close($mh);

        return true;
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
                call_user_func($callBack, $output, $info, $request);
            }
        } else {
            return ['info' => $info, 'output' => $output];
        }

        return true;
    }

}















