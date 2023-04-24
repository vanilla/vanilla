<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Http\CurlHandler;
use Garden\SafeCurl\SafeCurl;
use Garden\Http\HttpResponse;

/**
 * HTTP handler interface utilizing SafeCurl.
 */
class SafeCurlHttpHandler extends CurlHandler
{
    /** @var bool Should location headers be followed? */
    private $followLocation = true;

    /**
     * Execute a curl handle using the SafeCurl wrapper.
     *
     * @param resource $ch The curl handle to execute.
     * @return HttpResponse Returns an {@link RestResponse} object with the information from the request
     */
    protected function execCurl($ch)
    {
        $curlHandle = $ch;
        $url = curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL);
        $safeCurl = new SafeCurl($curlHandle);
        $safeCurl->setFollowLocation($this->followLocation);
        try {
            $response = $safeCurl->execute($url);
        } catch (\Exception $e) {
            // Safe curl tries to make some requests up front
            // To make sure we aren't following redirects into an internal service.
            // This can throw it's own exception as it validates the security of the URL.
            // However whether or not we throw an exception, depends on the configuration of the HttpClient instance.
            //
            // TL:DR; HttpHandlers should never throw. Only return responses (that could be an error response).
            $responseBody = [
                "error" => array_filter([
                    "message" => $e->getMessage(),
                    "code" => $e->getCode(),
                    "trace" => debug() ? $e->getTraceAsString() : null,
                ]),
            ];
            $response = new HttpResponse(500, ["content-type" => "application/json"], json_encode($responseBody));
        }

        return $response;
    }

    /**
     * Decode a curl response and turn it into
     *
     * @param resource $ch The curl handle.
     * @param string|HttpResponse $response Either a string or an http response.
     * @return HttpResponse
     */
    protected function decodeCurlResponse($ch, $response): HttpResponse
    {
        if ($response instanceof HttpResponse) {
            return $response;
        } else {
            return parent::decodeCurlResponse($ch, $response);
        }
    }

    /**
     * Set whether or not location headers should be followed.
     *
     * @param bool $followLocation
     */
    public function setFollowLocation(bool $followLocation)
    {
        $this->followLocation = $followLocation;
    }

    /**
     * Get whether or not location headers should be followed.
     *
     * @return bool
     */
    public function getFollowLocation(): bool
    {
        return $this->followLocation;
    }
}
