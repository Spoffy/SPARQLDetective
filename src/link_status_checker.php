<?php

//This exists purely so we can use RAII to prevent cURL handles from leaking
//Can't use try .. finally as we need to support PHP 5.2.
//TODO Make this use a rolling-window, so it doesn't busy wait on slow requests.
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

    function __construct(array $urls)
    {
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
        curl_multi_close($this->multiHandle);
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
        //TODO Make this not a magic constant.
        //Prevents the checker from hanging on a single entry for large periods of time.
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);
        curl_setopt($curlHandle, CURLOPT_USERAGENT,'SPARQL Detective Bot Broken Link Checker <https://github.com/Spoffy/SPARQLDetective/>' );
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

    private function codeToStatusMessage($statusCode, $httpCode) {
        $statusMessage = $httpCode;

        //httpCode should be 0 if we got a cURL error.
        //Checking statusCode doesn't work, as there's a HTTP error code.
        if($statusCode > 0 && $statusCode != CURLE_HTTP_NOT_FOUND) {
            if(key_exists($statusCode, LinkCheck::$cURLErrorCodeToMessage)) {
                $statusMessage = LinkCheck::$cURLErrorCodeToMessage[$statusCode];
            } else {
                //$statusMessage = curl_strerror($statusCode); // 5.5.0 or later
                $statusMessage = "CURL ERROR $statusCode"; // 5.5.0 or later
            }
        }
        return $statusMessage;
    }

    private function wasRequestSuccessful($statusCode, $httpCode) {
        if($httpCode >= 400 || $statusCode > 0) { return false; }
        return true;
    }

    private function createResult($statusCode, $handle) {
        $url = $this->handleToURL($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $success = $this->wasRequestSuccessful($statusCode, $httpCode);
        $statusMessage = $this->codeToStatusMessage($statusCode, $httpCode);
        
        return array(
            "url" => $url,
            "success" => $success,
            "statusMessage" => $statusMessage
        );
    }

    //We need to use curl_multi_info_read for error codes
    //Normal curl handles always return 0 (no error) when attached to the multi-handle.
    public function getResults() {
        $results = array();
        do {
            //Set by curl_multi_info_read
            $remaining_messages = 0;
            $info = curl_multi_info_read($this->multiHandle, $remaining_messages);
            if(!$info) break;

            $status_code = $info["result"];
            $handle = $info["handle"];
            $results[] = $this->createResult($status_code, $handle);
        } while($remaining_messages > 0);
        return $results;
    }

    public static function runTests() {
        $tests = array(
            "http://www.yahoo.co.uk",
            "http://www.google.co.uk/teapot",
            "http://www.google.com/404please",
            "http://www.google.de",
            "http://www.thereisnowaythisisactuallyadomainname.co.uk",
            "##########!!"
        );

        $result = new LinkCheck($tests);#

        foreach($result->getResults() as $item) {
            print("URL: " . $item->url . " Success: " . $item->success . " Code: " . $item->statusMessage . "\n");
        }
    }
}

