<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Http\CurlHandler;
use Garden\SafeCurl\SafeCurl;

/**
 * HTTP handler interface utilizing SafeCurl.
 */
class SafeCurlHttpHandler extends CurlHandler {
    /**
     * @var bool followLocation.
     */
    private $followLocation = true;
    /**
     * Execute a curl handle using the SafeCurl wrapper.
     *
     * @param resource $curlHandle The curl handle to execute.
     * @return HttpResponse Returns an {@link RestResponse} object with the information from the request
     */
    protected function execCurl($curlHandle) {
        $url = curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL);
        $safeCurl = new SafeCurl($curlHandle);
        $safeCurl->setFollowLocation($this->followLocation);
        $response = $safeCurl->execute($url);

        return $response;
    }

    /**
     * cURL followLocation setter.
     * @param bool $followLocation
     */
    public function setFollowLocation(bool $followLocation) {
        $this->followLocation = $followLocation;
    }

    /**
     * cURL followLocation getter.
     * @return bool
     */
    public function getFollowLocation(): bool {
        return $this->followLocation;
    }
}
