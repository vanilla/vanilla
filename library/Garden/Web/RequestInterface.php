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
     * Get all headers from the request.
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Get a header value.
     *
     * @param string $header The name of the header.
     * @return string Returns the header value or an empty string.
     */
    public function getHeader($header);

    /**
     * Get a header's value(s) as a string.
     *
     * @param string $name The name of the header.
     * @param string $default
     * @return string A header's value(s). Multiple values are returned as a CSV string.
     */
    public function getHeaderLine($name, $default = '');

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns **true** if the header exists or **false** otherwise.
     */
    public function hasHeader($header);
}
