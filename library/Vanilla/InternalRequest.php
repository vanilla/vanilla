<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

use Garden\Web\RequestInterface;

class InternalRequest implements RequestInterface {
    private $path;
    private $query;
    private $body;
    private $headers;
    private $method;

    public function __construct($method = 'GET', $path = '', array $body = [], array $headers = []) {
        $this->method = strtoupper($method);
        $this->path = '/'.ltrim($path, '/');

        if ($this->method === 'GET') {
            $this->query = $body;
        } else {
            $this->body = $body;
        }
        $this->headers = $headers;
    }

    /**
     * Get the hostname of the request.
     *
     * @return string
     */
    public function getHost() {
        return 'internal';
    }

    /**
     * Get the method used to do the request.
     *
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    public function getRoot() {
        return '';
    }

    /**
     * Get the path of the request.
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Get the query of the request.
     *
     * @return mixed
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * Get the body of the request.
     *
     * @return mixed
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Get the scheme of the request.
     *
     * @return string Either http or https.
     */
    public function getScheme() {
        return 'https';
    }

    /**
     * Get all headers from the request.
     *
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Get a header value.
     *
     * @param string $header The name of the header.
     * @return string Returns the header value or an empty string.
     */
    public function getHeader($header) {
        return isset($this->headers[$header]) ? $this->headers[$header] : '';
    }

    public function setHeader($header, $value) {
        $this->headers[$header] = $value;
        return $this;
    }

    /**
     * Get a header's value(s) as a string.
     *
     * @param string $name The name of the header.
     * @return string A header's value(s). Multiple values are returned as a CSV string.
     */
    public function getHeaderLine($name) {
        $header = $this->getHeader($name);
        if (is_array($header)) {
            $header = implode(',', $header);
        }
        return $header;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns **true** if the header exists or **false** otherwise.
     */
    public function hasHeader($header) {
        return !empty($this->headers[$header]);
    }

    /**
     * Set the body.
     *
     * @param mixed $body
     * @return $this
     */
    public function setBody($body) {
        $this->body = $body;
        return $this;
    }
}
