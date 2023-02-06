<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;

use Garden\Web\RequestInterface;
use Vanilla\Cache\InvalidArgumentException;

/**
 * A mock request object for testing.
 */
class Request implements RequestInterface
{
    private $scheme = "http";
    private $host = "example.com";
    private $assetRoot = "";
    private $method;
    private $root;
    private $path;
    private $query = [];
    private $body = [];
    private $headers = [];

    /**
     * Request constructor.
     *
     * @param string $path The path of the request.
     * @param string $method The HTTP method.
     * @param array $data The query for **GET** requests or the body for other requests.
     */
    public function __construct($path = "/", $method = "GET", array $data = [])
    {
        if (isUrl($path) || strpos($path, "?") !== false) {
            $parts = parse_url($path);
        } else {
            $parts = ["path" => $path];
        }
        $path = $parts["path"] ?? "/";
        $query = [];

        if (!empty($parts["query"])) {
            parse_str($parts["query"], $query);
        }

        if (in_array($method, ["GET"])) {
            $query += $data;
            $body = [];
        } else {
            $body = $data;
        }

        $this->scheme = $parts["scheme"] ?? "http";
        $this->host = $parts["host"] ?? "example.com";
        $this->root = "";
        $this->path = "/" . ltrim($path, "/");
        $this->method = $method;
        $this->query = $query;
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the path of the request.
     *
     * @param string $path The new path.
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = "/" . ltrim($path, "/");
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the HTTP method used to do the request.
     *
     * Any string can be given here, but it will be converted to uppercase.
     *
     * @param string $method The HTTP method.
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function setQuery(array $value)
    {
        $this->query = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function getRawBody(): string
    {
        return is_string($this->body) ? $this->body : json_encode($this->body, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Set the body of the message.
     *
     * @param string|array $body The new body of the message.
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set the scheme of the request.
     *
     * @param string $scheme One of "http" or "https".
     * @return $this
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Get the hostname of the request.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the hostname of the request.
     *
     * @param string $host The new hostname.
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set the root path of the request.
     *
     * @param string $root The new root path of the request.
     * @return $this
     */
    public function setRoot($root)
    {
        $root = trim($root, "/");
        $root = $root ? "/$root" : "";
        $this->root = $root;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAssetRoot()
    {
        return $this->assetRoot;
    }

    /**
     * @inheritdoc
     */
    public function setAssetRoot(string $root)
    {
        $this->assetRoot = rtrim("/" . trim($root, "/"), "/");
        return $this;
    }

    /**
     * Set a header on the request.
     *
     * @param string $header The header to set.
     * @param mixed $value The value of the header.
     * @return $this
     */
    public function setHeader(string $header, $value)
    {
        $this->headers[$header] = $value;
        return $this;
    }

    /**
     * Get a header value.
     *
     * @param string $header The name of the header.
     * @param mixed $default The default value if the header does not exist.
     * @return mixed Returns the header value or {@link $default}.
     */
    public function getHeader(string $header, $default = null)
    {
        return isset($this->headers[$header]) ? $this->headers[$header] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns **true** if the header exists or **false** otherwise.
     */
    public function hasHeader(string $header): bool
    {
        return !empty($this->headers[$header]);
    }

    /**
     * @inheritdoc
     */
    public function urlDomain($withDomain = true): string
    {
        if (!$withDomain) {
            return "";
        }
        return $this->scheme . "://" . $this->host;
    }

    /**
     * Get the URL without the querystring.
     *
     * @return string
     */
    public function getDomainAndPath(): string
    {
        return $this->urlDomain() . $this->getRoot() . $this->getPath();
    }

    /**
     * Box a request.
     *
     * @param Request|string $request
     * @return Request
     * @throws InvalidArgumentException Throws an exception when `$request` is the wrong type.
     */
    public static function box($request): Request
    {
        if ($request instanceof Request) {
            return $request;
        } elseif (is_string($request)) {
            return new Request($request);
        }
        throw new InvalidArgumentException("Request::box() expects a Request or a string.", 400);
    }

    /**
     * Remove an item from the query array.
     *
     * @param string $key The key of the item to remove.
     */
    public function removeQueryItem(string $key): void
    {
        unset($this->query[$key]);
    }
}
