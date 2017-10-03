<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;

use Garden\Web\RequestInterface;

/**
 * A mock request object for testing.
 */
class Request implements RequestInterface {
    private $method;
    private $path;
    private $query = [];
    private $body = [];
    private $headers = [];

    public function __construct($path = '/', $method = 'GET', array $data = []) {
        $query = [];

        if (strpos($path, '?')) {
            list($path, $queryString) = explode('?', $path, 2);
            parse_str($queryString, $query);
        }

        if (in_array($method, ['GET'])) {
            $query += $data;
            $body = [];
        } else {
            $body = $data;
        }

        $this->path = '/'.ltrim($path, '/');
        $this->method = $method;
        $this->query = $query;
        $this->body = $body;
    }


    public function getPath() {
        return $this->path;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getQuery() {
        return $this->query;
    }

    public function getBody() {
        return $this->body;
    }

    public function getScheme() {
        return 'http';
    }

    /**
     * Get the hostname of the request.
     *
     * @return string
     */
    public function getHost() {
        return 'example.com';
    }

    public function getRoot() {
        return '';
    }

    /**
     * Get a header value.
     *
     * @param string $header The name of the header.
     * @param mixed $default The default value if the header does not exist.
     * @return mixed Returns the header value or {@link $default}.
     */
    public function getHeader($header, $default = null) {
        return isset($this->headers[$header]) ? $this->headers[$header] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name) {
        $value = $this->getHeader($name);
        if (empty($value)) {
            $value = '';
        } elseif (is_array($value)) {
            $value = implode(',', $value);
        }
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders() {
        return $this->headers;
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
}
