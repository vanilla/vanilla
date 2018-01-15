<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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

    /**
     * Get the method used to do the request.
     *
     * @return string
     */
    public function getMethod();

    public function getRoot();

    /**
     * Get the path of the request.
     *
     * @return string
     */
    public function getPath();

    /**
     * Get the query of the request.
     *
     * @return mixed
     */
    public function getQuery();

    /**
     * Get the body of the request.
     *
     * @return mixed
     */
    public function getBody();

    /**
     * Get the scheme of the request.
     *
     * @return string Either http or https.
     */
    public function getScheme();

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
     * @return string A header's value(s). Multiple values are returned as a CSV string.
     */
    public function getHeaderLine($name);

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns **true** if the header exists or **false** otherwise.
     */
    public function hasHeader($header);
}
