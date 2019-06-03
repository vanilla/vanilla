<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
     * Set the hostname of the request.
     *
     * @param string $host The new hostname.
     * @return $this
     */
    public function setHost($host);

    /**
     * Get the method used to do the request.
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Set the HTTP method used to do the request.
     *
     * Any string can be given here, but it will be converted to uppercase.
     *
     * @param string $method The HTTP method.
     * @return $this
     */
    public function setMethod($method);

    /**
     * Get the root folder of the request.
     *
     * @return string Returns the root as a string.
     */
    public function getRoot();

    /**
     * Set the root path of the request.
     *
     * @param string $root The new root path of the request.
     * @return $this
     */
    public function setRoot($root);

    /**
     * Get the root folder for static files.
     *
     * @return string Returns the root as a string.
     */
    public function getAssetRoot();

    /**
     * Set the root folder for static files.
     *
     * @param string $root The new root path of files.
     * @return $this
     */
    public function setAssetRoot(string $root);

    /**
     * Get the path of the request.
     *
     * @return string
     */
    public function getPath();

    /**
     * Set the path of the request.
     *
     * @param string $path The new path.
     * @return $this
     */
    public function setPath($path);

    /**
     * Get the query of the request.
     *
     * @return array
     */
    public function getQuery();

    /**
     * Set the query for the request.
     *
     * @param array $value The new query.
     * @return $this
     */
    public function setQuery(array $value);

    /**
     * Get the body of the request.
     *
     * @return mixed
     */
    public function getBody();

    /**
     * Set the body of the message.
     *
     * @param string|array $body The new body of the message.
     * @return $this
     */
    public function setBody($body);

    /**
     * Get the scheme of the request.
     *
     * @return string Either http or https.
     */
    public function getScheme();

    /**
     * Set the scheme of the request.
     *
     * @param string $scheme One of "http" or "https".
     * @return $this
     */
    public function setScheme($scheme);

    /**
     * Get all headers from the request.
     *
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Get a header value.
     *
     * @param string $header The name of the header.
     * @return string Returns the header value or an empty string.
     */
    public function getHeader($header): string;

    /**
     * Set a header value.
     *
     * @param string $header The name of the header.
     * @param mixed $value The new value.
     * @return $this
     */
    public function setHeader(string $header, $value);

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns **true** if the header exists or **false** otherwise.
     */
    public function hasHeader(string $header): bool;

    /**
     * Conditionally gets the domain of the request.
     *
     * @param string|bool $withDomain Information about how to return the domain.
     * @return string Returns the domain.
     */
    public function urlDomain($withDomain = true);
}
