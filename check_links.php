<?php

function check_links(array $urls) {
    $link_status = array();
    foreach($urls as $url) {
        $curlHandle = curl_init($url);
        //Force it to use get requests
        curl_setopt($curlHandle, CURLOPT_HTTPGET, true);
        //Force a fresh connection for each request. Not sure if this is needed...
        curl_setopt($curlHandle, CURLOPT_FRESH_CONNECT, true);
        //Get Headers in case we need Location or other.
        curl_setopt($curlHandle, CURLOPT_HEADER, true);
        //Attempt to follow redirects
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        //Do we care about SSL certificates when checking a link is broken?
        //...Possibly if there's SSL errors. V2.
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);

        //Don't actually care about the output...
        ob_start();
        $result = curl_exec($curlHandle);
        ob_end_clean();

        $link_status[$url] = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        #print(curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL));
        #print(curl_error($curlHandle));
        curl_close($curlHandle);
    }
    return $link_status;
}

$result = check_links(array("http://www.yahoo.co.uk"));#

foreach($result as $url => $status) {
    print("URL: " . $url . " - " . $status);
}