<?php

require __DIR__ . '/../src/RollingCurlService/RollingCurl.php';
require __DIR__ . '/../src/RollingCurlService/RollingCurlRequest.php';

// Urls to be fetched
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