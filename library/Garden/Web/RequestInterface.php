<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Web;


interface RequestInterface {
    /**
     * Get the hostname of the request.
     *
     * @return string
     */
    public function getHost();

    public function getMethod();

    public function getRoot();

    public function getPath();

    public function getQuery();

    public function getBody();
}