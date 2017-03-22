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

    /**
     * Get a header value.
     *
     * @param string $header The name of the header.
     * @param mixed $default The default value if the header does not exist.
     * @return mixed Returns the header value or {@link $default}.
     */
    public function getHeader($header, $default = null);

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns **true** if the header exists or **false** otherwise.
     */
    public function hasHeader($header);
}