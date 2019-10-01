<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\Http\HttpHandlerInterface;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Web\Dispatcher;
use Garden\Web\RequestInterface;

class InternalRequest extends HttpRequest implements RequestInterface {
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var Container
     */
    private $container;

    public function __construct(
        Dispatcher $dispatcher,
        Container $container,
        $method = self::METHOD_GET,
        $url = '',
        $body = '',
        array $headers = [],
        array $options = []
    ) {
        parent::__construct($method, $url, $body, $headers, $options);
        $this->dispatcher = $dispatcher;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost() {
        return parse_url($this->getUrl(), PHP_URL_HOST);
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme() {
        return parse_url($this->url, PHP_URL_SCHEME);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery() {
        parse_str(parse_url($this->getUrl(), PHP_URL_QUERY), $query);
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function setQuery(array $value) {
        list($url, $query) = explode('?', $this->getUrl(), 1) + ['', ''];

        if (empty($value)) {
            $this->setUrl($url);
        } else {
            $this->setUrl($url.'?'.http_build_query($value));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send(?HttpHandlerInterface $executor = null): HttpResponse {
        $this->container->setInstance(\Gdn_Request::class, $this->convertToLegacyRequest());

        $cookieStash = $_COOKIE;
        $cookies = [];
        if ($rawCookies = $this->getHeader('Cookie')) {
            $rawCookies = explode(';', $rawCookies);
            array_walk($rawCookies, 'trim');
            foreach ($rawCookies as $cookie) {
                if (strpos($cookie, '=') === false) {
                    continue;
                }
                list($key, $val) = explode('=', $cookie);
                $cookies[$key] = rawurldecode($val);
            }
        }

        $_COOKIE = $cookies;
        $data = $this->dispatcher->dispatch($this);
        // Render the view in case it updates the Data object.
        ob_start();
        $this->dispatcher->render($this->container->get(\Gdn_Request::class), $data);
        ob_end_clean();
        $_COOKIE = $cookieStash;

        if ($ex = $data->getMeta('exception')) {
            /* @var \Throwable $ex */
            $data->setMeta('errorTrace', $ex->getTraceAsString());
        }

        $response = new HttpResponse(
            $data->getStatus(),
            array_merge($data->getHeaders(), ['X-Data-Meta' => json_encode($data->getMetaArray())])
        );
        // Simulate that the data was sent over HTTP and thus was serialized.
        $body = $data->jsonSerialize();
        $response->setBody($body);

        return $response;
    }

    /**
     * Convert this request into a legacy request.
     *
     * Many objects still depend on the Gdn_Request so we must add it to the container.
     *
     * @return \Gdn_Request
     */
    private function convertToLegacyRequest() {
        $request = new \Gdn_Request();

        $request->setUrl($this->getUrl());
        $request->setMethod($this->getMethod());
        if (!empty($this->getBody())) {
            $request->setBody($this->getBody());
        }

        return $request;
    }

    /**
     * Set the hostname of the request.
     *
     * @param string $host The new hostname.
     * @return $this
     */
    public function setHost($host) {
        $url = static::buildUrl(array_replace(parse_url($this->getUrl()), ['host' => $host]));
        $this->setUrl($url);
        return $this;
    }

    /**
     * Build a full URL based on parts from **parse_url()**.
     *
     * @param array $parts The URL parts to build.
     * @return string
     */
    protected static function buildUrl(array $parts) {
        $result = (!empty($parts['scheme']) ? $parts['scheme'].'://' : '')
            .(!empty($parts['user']) ? $parts['user'].(!empty($parts['pass']) ? ':'.$parts['pass'] : '').'@' : '')
            .(!empty($parts['host']) ? $parts['host'] : '')
            .(!empty($parts['port']) ? ':'.$parts['port'] : '')
            .(!empty($parts['path']) ? $parts['path'] : '')
            .(!empty($parts['query']) ? '?'.$parts['query'] : '')
            .(!empty($parts['fragment']) ? '#'.$parts['fragment'] : '');

        return $result;
    }

//    public function handleErrorResponse(HttpResponse $response, $options = []) {
//        if ($this->val('throw', $options, $this->throwExceptions)) {
//            $body = $response->getBody();
//            if (is_array($body)) {
//                $message = $this->val('message', $body, $response->getReasonPhrase());
//            } else {
//                $message = $response->getReasonPhrase();
//            }
//            throw new \Exception($message, $response->getStatusCode());
//        }
//    }

    /**
     * Set the path of the request.
     *
     * @param string $path The new path.
     * @return $this
     */
    public function setPath($path) {
        $path = ltrim($path, '/');
        if (!empty($path)) {
            $path = "/$path";
        }

        $url = static::buildUrl(array_replace(parse_url($this->getUrl()), ['path' => $this->getRoot().$path]));
        $this->setUrl($url);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot() {
        $root = trim(parse_url($this->container->get('@baseUrl'), PHP_URL_PATH), '/');
        $root = $root ? "/$root" : '';
        return $root;
    }

    /**
     * Set the scheme of the request.
     *
     * @param string $scheme One of "http" or "https".
     * @return $this
     */
    public function setScheme($scheme) {
        $url = static::buildUrl(array_replace(parse_url($this->getUrl()), ['scheme' => strtoupper($scheme)]));
        $this->setUrl($url);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAssetRoot() {
        return $this->getRoot();
    }

    /**
     * @inheritdoc
     */
    public function setAssetRoot(string $root) {
        $this->setRoot($root);
    }

    /**
     * @inheritdoc
     */
    public function urlDomain($withDomain = true): string {
        return "";
    }

    /**
     * Set the root path of the request.
     *
     * @param string $root The new root path of the request.
     * @return $this
     */
    public function setRoot($root) {
        $root = trim($root, '/');
        $root = $root ? '' : "/$root";
        $path = $this->getPath();

        $url = static::buildUrl(array_replace(parse_url($this->getUrl()), ['path' => $root.$path]));
        $this->setUrl($url);

        $baseUrl = static::buildUrl(array_replace(parse_url($this->container->get('@baseUrl')), ['path' => $root]));
        $this->container->setInstance('@baseUrl', $baseUrl);

        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath() {
        $path = parse_url($this->getUrl(), PHP_URL_PATH);
        $root = $this->getRoot();

        if (strpos($path, $root) === 0) {
            $testPath = substr($path, strlen($root));
            if (empty($testPath)) {
                $path = '/';
            } elseif ($testPath[0] = '/') {
                $path = $testPath;
            }
        }

        return $path;
    }
}
