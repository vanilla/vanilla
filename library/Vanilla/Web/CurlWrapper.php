<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\SafeCurl\SafeCurl;
use Garden\SafeCurl\Exception;

/*
 * SafeCurl wrapper.
 */
class CurlWrapper {
    /**
     * Executes a safecurl request.
     *
     * @param resource $ch The curl handle to execute.
     * @param bool $followLocation
     * @return mixed
     */
    public static function curlExec($ch, bool $followLocation = false) {
        $safeCurl = new SafeCurl($ch);
        $safeCurl->setFollowLocation($followLocation);
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        if (!$url) {
            throw new Exception('Could not get the url.');
        }
        $response = $safeCurl->execute($url);
        return $response;
    }
}
