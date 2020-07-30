# RollingCurlService

A non blocking multi thread cURL library


## Overview

curl_multi() makes it possible to handle multiple HTTP requests in parallel in PHP.  
RollingCurl is a specific implementation of curl_multi() that prevents from executing a whole stack of requests at once and having to wait for the slowest response.


RollingCurl starts by executing only a portion of a request stack and adding a new request each time a request has completed, while other requests are still running.

Features of this implementation:

* Add custom data to a request. This data is then available when a request has completed (e.g. the original url, or an ID)
* Set custom cURL options for all requests
* Add custom cURL options to the global options for a single request or replace the global options by custom options for a single request
* Write request response to file (local or distant using FTP)
* Custom rollingWindow (number of parallel requests)


## Installation (via composer)

[Get composer](http://getcomposer.org/doc/00-intro.md) and add this in your requires section of the composer.json:

```
{
    "require": {
        "pepsia/rolling-curl": "*"
    }
}
```

and then

```
composer install
```

## Usage

### Basic Example

```php
$urls = [
    'https://en.wikipedia.org/wiki/Moon',
    'https://en.wikipedia.org/wiki/Earth',
    'https://en.wikipedia.org/wiki/Saturn',
    'https://en.wikipedia.org/wiki/Jupiter',
    'https://en.wikipedia.org/wiki/Mars'
];

$rollingCurl = new \RollingCurlService\RollingCurl();

foreach ($urls as $key => $url) {
    $request = new \RollingCurlService\RollingCurlRequest($url);
    // RollingCurlRequest attributes is additional data that can be retrieved in curl callback
    $request->setAttributes([
        'requestId'   => $key // Some ID for the request
    ]);

    $rollingCurl->addRequest($request);
}

$curlResult = [];

$rollingCurl->execute(function ($output, $info, $request) use (& $curlResult)
{
    $requestAttributes = $request->getAttributes();
    // If request response was OK
    if ($info['http_code'] == 200) {
        $curlResult[$requestAttributes['requestId']] = $output;
    // If request response was KO
    } elseif ($info['http_code'] != 200) {
        $curlResult[$requestAttributes['requestId']] = 'KO response';
    }
});

var_dump($curlResult);
```

### Write request output to file


```php
$urls = [
    'https://en.wikipedia.org/wiki/Moon',
    'https://en.wikipedia.org/wiki/Earth',
    'https://en.wikipedia.org/wiki/Saturn',
    'https://en.wikipedia.org/wiki/Jupiter',
    'https://en.wikipedia.org/wiki/Mars'
];

$rollingCurl = new \RollingCurlService\RollingCurl();

foreach ($urls as $key => $url) {
    $request = new \RollingCurlService\RollingCurlRequest($url);

    $request->setFileToWrite('/my/path/my_file.txt'); 
    // This also supports an FTP target: 'ftp://user:pass@host/my/path/my_video.mp4'

    $rollingCurl->addRequest($request);
}

$filesCount = 0;

$rollingCurl->execute(function ($output, $info, $request) use (& $filesCount)
{
    if ($info['http_code'] == 200) {
        $filesCount++;
        // If a file write path was provided => output was written directly to file
    } 
});

var_dump($filesCount);
```

### Setting custom curl options

For *every* request

```php
$urls = [
    'https://en.wikipedia.org/wiki/Moon',
    'https://en.wikipedia.org/wiki/Earth',
    'https://en.wikipedia.org/wiki/Saturn',
    'https://en.wikipedia.org/wiki/Jupiter',
    'https://en.wikipedia.org/wiki/Mars'
];

$rollingCurl = new \RollingCurlService\RollingCurl();

foreach ($urls as $key => $url) {
    $request = new \RollingCurlService\RollingCurlRequest($url);
    $rollingCurl->addRequest($request);
}

// Set options for all requests by passing options array
$rollingCurl->setOptions([
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_TIMEOUT   => 60,
]);

$rollingCurl->execute();
```

For *a single* request:

```php
$urls = [
    'https://en.wikipedia.org/wiki/Moon'    => [CURLOPT_TIMEOUT => 15],
    'https://en.wikipedia.org/wiki/Earth'   => [CURLOPT_TIMEOUT => 5],
    'https://en.wikipedia.org/wiki/Saturn'  => [CURLOPT_TIMEOUT => 25],
    'https://en.wikipedia.org/wiki/Jupiter' => [CURLOPT_TIMEOUT => 10],
    'https://en.wikipedia.org/wiki/Mars'    => [CURLOPT_TIMEOUT => 15]
];

$rollingCurl = new \RollingCurlService\RollingCurl();

/*
    If setOptions() optional "addToGlobalOptions" param is set to TRUE the cURL options 
    for that single request will be added to the global options instead of replacing them.
*/
foreach ($urls as $url => $options) {
    $request = new \RollingCurlService\RollingCurlRequest($url);
    $request->setOptions($options, TRUE);
    $rollingCurl->addRequest($request);
}

$rollingCurl->execute();
```


