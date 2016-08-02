<?php

class LinkCheckResult {
    public $url;
    public $status;
    public $statusMessage;

    public function __construct($url, $status, $statusMessage)
    {
        $this->url = $url;
        $this->status = $status;
        $this->statusMessage = $statusMessage;
    }
}

//This exists purely so we can use RAII to prevent cURL handles from leaking
//Can't use try .. finally as we need to support PHP 5.2.
class LinkCheck {
    //These should only be written to or removed from in Constructor and destructor.
    //Accessing their contents is fine.
    private $handles;
    private $multiHandle;

    //Status messages we want to customise.
    //In this case, all the ones we're interested in.
    private static $cURLErrorCodeToMessage = array(
        CURLE_UNSUPPORTED_PROTOCOL => "Unsupported Protocol",
        CURLE_URL_MALFORMAT => "Malformed URL",
        CURLE_COULDNT_RESOLVE_PROXY => "Invalid Proxy Configured",
        CURLE_COULDNT_RESOLVE_HOST => "Unable to resolve host",
        CURLE_COULDNT_CONNECT => "Unable to connect to host",
        CURLE_OPERATION_TIMEOUTED => "Operation Timed Out",
        CURLE_SSL_CONNECT_ERROR => "SSL/TLS handshake error",
        CURLE_SSL_CERTPROBLEM => "Problem with local SSL certificate",
        CURLE_SSL_CIPHER => "Unable to use specified SSL cipher",
        CURLE_SSL_CACERT => "Untrusted remote SSL certificate"
    );

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
        curl_setopt($curlHandle, CURLOPT_FAILONERROR, true);
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
            $handles[$url] = $this->createHandleForURL($url);
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

    private function handleToURL($sourceHandle) {
        foreach($this->handles as $url => $currentHandle) {
            if($sourceHandle == $currentHandle) {
                return $url;
            }
        }
        throw new Exception("Failed to get URL associated with Handle. This should NEVER occur. This is a bug.");
    }

    private function codeToStatusMessage($statusCode, $handle) {
        $statusMessage = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if($statusCode) {
            if(key_exists($statusCode, LinkCheck::$cURLErrorCodeToMessage)) {
                $statusMessage = LinkCheck::$cURLErrorCodeToMessage[$statusCode];
            } else {
                $statusMessage = curl_strerror($statusCode);
            }
        }
        return $statusMessage;
    }

    private function createResult($status_code, $handle) {
        $url = $this->handleToURL($handle);
        $success = false;
        $status_message = $this->codeToStatusMessage($status_code, $handle);

        return new LinkCheckResult($url, $success, $status_message);
    }

    //We need to use curl_multi_info_read for error codes
    //Normal curl handles always return 0 (no error) when attached to the multi-handle.
    public function getResults() {
        $results = array();
        do {
            //Set by curl_multi_info_read
            $remaining_messages = 0;
            $info = curl_multi_info_read($this->multiHandle, $remaining_messages);

            $status_code = $info["result"];
            $handle = $info["handle"];
            $results[] = $this->createResult($status_code, $handle);
        } while ($remaining_messages > 0);
        return $results;
    }
}

$tests = array(
    "http://www.yahoo.co.uk",
    "http://www.google.co.uk",
    "http://www.google.com",
    "http://www.google.de",
    "http://www.googleasdqgqeg.co.uk"
);
$result = new LinkCheck($tests);#

foreach($result->getResults() as $item) {
    print("URL: " . $item->url . " Code: " . $item->statusMessage . "\n");
}