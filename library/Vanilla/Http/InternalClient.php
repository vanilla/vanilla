<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Http;

use Garden\Container\Container;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Sites\Clients\SiteHttpClient;
use Garden\Web\Data;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\ResponseException;
use Vanilla\Site\OwnSite;
use Vanilla\Utility\DebugUtils;

/**
 * Http client for making requests internally against the dispatcher.
 */
class InternalClient extends SiteHttpClient
{
    const DEFAULT_USER_ID = 2;

    /**
     * @var Container The container used to construct request objects.
     */
    private $container;

    /**
     * @var int
     */
    private $userID = self::DEFAULT_USER_ID;

    /**
     * @var string
     */
    private $transientKey;

    /**
     * @var string
     */
    private $transientKeySigned;

    /**
     * InternalClient constructor.
     *
     * @param Container $container The container used to create requests.
     * @param OwnSite $ownSite
     * @param string $baseUrl The base Url for relative path requests.
     */
    public function __construct(Container $container, OwnSite $ownSite, $baseUrl = "/api/v2")
    {
        parent::__construct($ownSite);
        $this->setBaseUrl($baseUrl);
        $this->throwExceptions = true;
        $this->container = $container;
        $this->setDefaultOption("timeout", 25);
    }

    /**
     * Add the transient key cookie header to an array of headers.
     *
     * @param array $headers
     */
    public function addTransientKeyHeader(array &$headers)
    {
        /** @var \Gdn_Configuration $config */
        $config = $session = $this->container->get(\Gdn_Configuration::class);
        $name = $config->get("Garden.Cookie.Name") . "-tk";
        $value = rawurlencode($this->transientKeySigned);
        $cookies = array_key_exists("Cookie", $headers) ? rtrim($headers["Cookie"], "; ") . "; " : "";
        $cookies .= "$name=$value;";
        $headers["Cookie"] = $cookies;
    }

    /**
     * {@inheritdoc}
     */
    public function createRequest(string $method, string $uri, $body, array $headers = [], array $options = [])
    {
        // If we already have our own domain, strip it off.
        $uri = str_replace($this->baseUrl, "", $uri);

        if (strpos($uri, "//") === false) {
            $uri = $this->baseUrl . "/" . ltrim($uri, "/");
        }

        $headers = array_replace($this->defaultHeaders, $headers);
        $options = array_replace($this->defaultOptions, $options);

        $request = $this->container->getArgs(InternalRequest::class, [$method, $uri, $body, $headers, $options]);
        return $request;
    }

    /**
     * Send a GET request to the API and include the transient key.
     *
     * @param string $uri The URL or path of the request.
     * @param array $query The querystring to add to the URL.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function getWithTransientKey($uri, array $query = [], array $headers = [], $options = [])
    {
        $this->addTransientKeyHeader($headers);
        $query["TransientKey"] = $this->getTransientKey();
        $result = $this->get($uri, $query, $headers, $options);
        return $result;
    }

    /**
     * Handle a non 200 series response from the API.
     *
     * @param HttpResponse $response The response to process.
     * @param array $options Options from the request invocation.
     * @throws \Exception Throws an exception representing the error.
     */
    public function handleErrorResponse(HttpResponse $response, $options = [])
    {
        if ($this->val("throw", $options, $this->throwExceptions)) {
            $body = $response->getBody();
            if (is_array($body)) {
                if (!empty($body["errors"])) {
                    // Concatenate all errors together.
                    $messages = array_column($body["errors"], "message");
                    $message = implode(" ", $messages);
                } else {
                    $message = $this->val("message", $body, $response->getReasonPhrase());
                }
            } else {
                $message = $response->getReasonPhrase();
            }

            if (!empty($body["errors"]) && count($body["errors"]) > 1) {
                $message .= " " . implode(" ", array_column($body["errors"], "message"));
            }

            $previousEx = null;
            if ($response instanceof InternalResponse) {
                $throwable = $response->getThrowable();
                if ($throwable instanceof \Throwable) {
                    $previousEx = $throwable;
                }
            }

            $exception = HttpException::createFromStatus(
                $response->getStatusCode(),
                $message,
                is_array($body) ? $body : [],
                $previousEx
            );

            if ($previousEx instanceof \Throwable) {
                if ($previousEx instanceof HttpException) {
                    $exception = $exception->withContext($previousEx->getContext());
                }

                if (DebugUtils::isDebug() || DebugUtils::isTestMode()) {
                    $traces = [];
                    $ex = $previousEx;
                    while ($ex !== null) {
                        $traces[] = DebugUtils::stackTraceString($ex->getTrace());
                        $ex = $ex->getPrevious();
                    }
                    $trace = implode("\n\nCaused By\n\n", $traces);

                    $exception = $exception->withContext([
                        "trace" => $trace,
                    ]);
                }
            }
            throw $exception;
        }
    }

    /**
     * Get the configured transient key.
     *
     * @return string
     */
    public function getTransientKey()
    {
        return $this->transientKey;
    }

    /**
     * Get the user ID that will be used to make requests.
     *
     * @return int Returns the userID.
     */
    public function getUserID()
    {
        return $this->userID;
    }

    /**
     * Send a POST request to the API and include the transient key.
     *
     * @param string $uri The URL or path of the request.
     * @param array $body The HTTP body to send to the request.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function postWithTransientKey($uri, array $body = [], array $headers = [], $options = [])
    {
        $this->addTransientKeyHeader($headers);
        $body["TransientKey"] = $this->getTransientKey();
        $result = $this->post($uri, $body, $headers, $options);
        return $result;
    }

    /**
     * Configure the transient key to be used for requests.
     *
     * @param string $transientKey
     * @return $this
     * @throws \Exception if no active user session is available.
     */
    public function setTransientKey($transientKey)
    {
        $this->transientKey = $transientKey;

        /** @var \Gdn_Session $session */
        $session = $this->container->get(\Gdn_Session::class);
        if ($session->UserID) {
            $session->transientKey($transientKey);
            $payload = $session->generateTKPayload($transientKey);
            $signature = $session->generateTKSignature($payload);
            $this->transientKeySigned = "$payload:$signature";
        } else {
            throw new \Exception("Cannot build transient key payload without an active session.");
        }

        return $this;
    }

    /**
     * Set the user ID that will be used to make requests.
     *
     * @param int $userID The new user ID.
     * @return $this
     */
    public function setUserID($userID)
    {
        $this->userID = $userID;

        /* @var \Gdn_Session $session */
        $session = $this->container->get(\Gdn_Session::class);
        if ($userID === 0) {
            $session->end();
        } else {
            $session->start($userID, false, false);
        }

        return $this;
    }

    /**
     * Set a permission for the current session.
     *
     * @param string $permission The name of the permission to set.
     * @param bool $value The new value.
     * @return bool Returns the previous value.
     */
    public function setPermission(string $permission, bool $value): bool
    {
        /* @var \Gdn_Session $session */
        $session = $this->container->get(\Gdn_Session::class);
        $previous = $session->getPermissions()->has($permission);
        $session->getPermissions()->set($permission, $value);
        return $previous;
    }

    /**
     * Send a DELETE request to the API with a body instead of params.
     *
     * @param string $uri The URL or path of the request.
     * @param array $body The querystring to add to the URL.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function deleteWithBody(
        string $uri,
        array $body = [],
        array $headers = [],
        array $options = []
    ): HttpResponse {
        return $this->request(HttpRequest::METHOD_DELETE, $uri, $body, $headers, $options);
    }

    /**
     * @param HttpResponse $response
     * @return InternalResponse
     */
    private function ensureInternalResponse(HttpResponse $response): InternalResponse
    {
        if ($response instanceof InternalResponse) {
            return $response;
        }

        $data = new Data($response->getBody(), $response->getStatusCode(), $response->getHeaders());
        return new InternalResponse($data);
    }

    /**
     * Wrap in {@link InternalResponse}
     * @inheritDoc
     */
    public function request(
        string $method,
        string $uri,
        $body,
        array $headers = [],
        array $options = []
    ): InternalResponse {
        return $this->ensureInternalResponse(parent::request($method, $uri, $body, $headers, $options));
    }

    /**
     * Wrap in {@link InternalResponse}
     * @inheritDoc
     */
    public function get(string $uri, array $query = [], array $headers = [], $options = []): InternalResponse
    {
        return $this->ensureInternalResponse(parent::get($uri, $query, $headers, $options));
    }

    /**
     * Wrap in {@link InternalResponse}
     * @inheritDoc
     */
    public function post(string $uri, $body = [], array $headers = [], $options = []): InternalResponse
    {
        return $this->ensureInternalResponse(parent::post($uri, $body, $headers, $options));
    }

    /**
     * Wrap in {@link InternalResponse}
     * @inheritDoc
     */
    public function patch(string $uri, $body = [], array $headers = [], $options = []): InternalResponse
    {
        return $this->ensureInternalResponse(parent::patch($uri, $body, $headers, $options));
    }

    /**
     * Wrap in {@link InternalResponse}
     * @inheritDoc
     */
    public function delete(string $uri, array $query = [], array $headers = [], array $options = []): InternalResponse
    {
        return $this->ensureInternalResponse(parent::delete($uri, $query, $headers, $options));
    }
}
