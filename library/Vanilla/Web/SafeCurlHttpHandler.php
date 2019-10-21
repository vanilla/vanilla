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

    /** @var bool Should location headers be followed? */
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
     * Set whether or not location headers should be followed.
     *
     * @param bool $followLocation
     */
    public function setFollowLocation(bool $followLocation) {
        $this->followLocation = $followLocation;
    }

    /**
     * Get whether or not location headers should be followed.
     *
     * @return bool
     */
    public function getFollowLocation(): bool {
        return $this->followLocation;
    }
}
