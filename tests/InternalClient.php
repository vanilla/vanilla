<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;
use Garden\Web\Exception\HttpException;

class InternalClient extends HttpClient {

    /**
     * @var Container The container used to construct request objects.
     */
    private $container;

    /**
     * @var int
     */
    private $userID;

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
     * @param string $baseUrl The base Url for relative path requests.
     */
    public function __construct(Container $container, $baseUrl = '/api/v2') {
        parent::__construct($baseUrl);
        $this->throwExceptions = true;
        $this->container = $container;
    }

    /**
     * Add the transient key cookie header to an array of headers.
     *
     * @param array $headers
     */
    public function addTransientKeyHeader(array &$headers) {
        /** @var \Gdn_Configuration $config */
        $config = $session = $this->container->get(\Gdn_Configuration::class);
        $name = $config->get('Garden.Cookie.Name').'-tk';
        $value = rawurlencode($this->transientKeySigned);
        $cookies = array_key_exists('Cookie', $headers) ? rtrim($headers['Cookie'], '; ').'; ' : '';
        $cookies .= "$name=$value;";
        $headers['Cookie'] = $cookies;
    }

    /**
     * {@inheritdoc}
     */
    public function createRequest(string $method, string $uri, $body, array $headers = [], array $options = []) {
        if (strpos($uri, '//') === false) {
            $uri = $this->baseUrl.'/'.ltrim($uri, '/');
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
    public function getWithTransientKey($uri, array $query = [], array $headers = [], $options = []) {
        $this->addTransientKeyHeader($headers);
        $query['TransientKey'] = $this->getTransientKey();
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
    public function handleErrorResponse(HttpResponse $response, $options = []) {
        if ($this->val('throw', $options, $this->throwExceptions)) {
            $body = $response->getBody();
            if (is_array($body)) {
                if (!empty($body['errors'])) {
                    // Concatenate all errors together.
                    $messages = array_column($body['errors'], 'message');
                    $message = implode(' ', $messages);
                } else {
                    $message = $this->val('message', $body, $response->getReasonPhrase());
                }
            } else {
                $message = $response->getReasonPhrase();
            }

            if (!empty($body['errors']) && count($body['errors']) > 1) {
                $message .= ' '.implode(' ', array_column($body['errors'], 'message'));
            }

            $dataMeta = json_decode($response->getHeader('X-Data-Meta'), true);
            if (!empty($dataMeta['errorTrace'])) {
                $message .= "\n".$dataMeta['errorTrace'];
            }

            throw HttpException::createFromStatus($response->getStatusCode(), $message, $body);
        }
    }

    /**
     * Get the configured transient key.
     *
     * @return string
     */
    public function getTransientKey() {
        return $this->transientKey;
    }

    /**
     * Get the user ID that will be used to make requests.
     *
     * @return int Returns the userID.
     */
    public function getUserID() {
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
    public function postWithTransientKey($uri, array $body = [], array $headers = [], $options = []) {
        $this->addTransientKeyHeader($headers);
        $body['TransientKey'] = $this->getTransientKey();
        $result = $this->post($uri, $body, $headers, $options);
        return $result;
    }

    /**
     * Configure the transient key to be used for requests.
     *
     * @param string $transientKey
     * @throws \Exception if no active user session is available.
     * @return $this
     */
    public function setTransientKey($transientKey) {
        $this->transientKey = $transientKey;

        /** @var \Gdn_Session $session */
        $session = $this->container->get(\Gdn_Session::class);
        if ($session->UserID) {
            $session->transientKey($transientKey);
            $payload = $session->generateTKPayload($transientKey);
            $signature = $session->generateTKSignature($payload);
            $this->transientKeySigned = "$payload:$signature";
        } else {
            throw new \Exception('Cannot build transient key payload without an active session.');
        }

        return $this;
    }

    /**
     * Set the user ID that will be used to make requests.
     *
     * @param int $userID The new user ID.
     * @return $this
     */
    public function setUserID($userID) {
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
    public function setPermission(string $permission, bool $value): bool {
        /* @var \Gdn_Session $session */
        $session = $this->container->get(\Gdn_Session::class);
        $previous = $session->getPermissions()->has($permission);
        $session->getPermissions()->set($permission, $value);
        return $previous;
    }
}
