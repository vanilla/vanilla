<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;
use Garden\SafeCurl\SafeCurl;

Class CurlWrapper {

    Static function curlExec($url, $ch, $followLocation = false) {
        $safeCurl = new SafeCurl($ch);
        $safeCurl->setFollowLocation($followLocation);
        return $safeCurl->execute($url);
    }
}
