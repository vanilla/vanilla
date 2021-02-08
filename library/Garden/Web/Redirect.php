<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web;

/**
 * Respresents a response to redirect to another site.
 */
class Redirect extends Data {
    /**
     * Redirect constructor.
     *
     * @param string $url The URL to redirect to.
     * @param int $status The HTTP status code (301 or 302).
     */
    public function __construct(string $url, int $status = 302) {
        parent::__construct(null, ['status' => $status], ['Location' => $url]);
    }
}
