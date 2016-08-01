<?php

//This exists purely so we can use RAII to prevent cURL handles from leaking
//Can't use try .. finally as we need to support PHP 5.2.
class LinkCheck {
    //These should only be written to or removed from in Constructor and destructor.
    //Accessing their contents is fine.
    private $handles;
    private $multiHandle;

    function __construct($urls)
    {
        $this->handles = $this->urlsToHandles($urls);
        $this->handles = $this->urlsToHandles($urls);
        $this->multiHandle = $this->createMultiHandle($this->handles);
        $this->executeMultiHandle($this->multiHandle);
    }

    function __destruct()
    {
        foreach($this->handles as $handle) {
            curl_multi_remove_handle($this->multiHandle, $handle);
            curl_close($handle);
        }
    }

    private function createHandleForURL($url) {
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
        return $curlHandle;
    }

    private function urlsToHandles($urls) {
        $handles = array();
        foreach($urls as $url) {
            $handles[] = $this->createHandleForURL($url);
        }
        return $handles;
    }

    private function createMultiHandle(array $handles) {
        $multiHandle = curl_multi_init();
        foreach($handles as $handle) {
            curl_multi_add_handle($multiHandle, $handle);
        }
        return $multiHandle;
    }

    //Warning: Processes until all URLs are done
    //Needs rewriting to be shorter, it totally could be.
    private function executeMultiHandle($multiHandle) {
        $active = null;
        $count = 0;
        do {
            ob_start();
            $mrc = curl_multi_exec($multiHandle, $active);
            ob_end_clean();
            $count++;
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
                do {
                    ob_start();
                    $mrc = curl_multi_exec($multiHandle, $active);
                    ob_end_clean();
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            //}
        }
    }

    public function getResults() {
        $results = array();
        foreach($this->handles as $handler) {
            $results[curl_getinfo($handler, CURLINFO_EFFECTIVE_URL)] = curl_getinfo($handler, CURLINFO_HTTP_CODE);
        }
        return $results;
    }
}

$tests = array(
    "http://www.yahoo.co.uk",
    "http://www.google.co.uk",
    "http://www.google.com",
    "http://www.google.de",
    "http://www.google.co.u"
);
$result = new LinkCheck($tests);#

foreach($result->getResults() as $url => $code) {
    print("URL: " . $url . " Code: " . $code . "\n");
}