<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests;

use Garden\Container\Container;
use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;

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
     * {@inheritdoc}
     */
    public function createRequest($method, $uri, $body, array $headers = [], array $options = []) {
        if (strpos($uri, '//') === false) {
            $uri = $this->baseUrl.'/'.ltrim($uri, '/');
        }

        /** @var \Gdn_Configuration $config */
        $config = $session = $this->container->get(\Gdn_Configuration::class);
        $headers = array_replace($this->defaultHeaders, $headers);
        $options = array_replace($this->defaultOptions, $options);

        $tkCookie = $config->get('Garden.Cookie.Name')."-tk={$this->transientKeySigned}";
        $headers['Cookie'] = array_key_exists('Cookie', $headers) ? $headers['Cookie']."; $tkCookie" : $tkCookie;

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
        $query['TransientKey'] = $this->getTransientKey();
        $result = $this->get($uri, $query, $headers, $options);
        return $result;
    }

    public function handleErrorResponse(HttpResponse $response, $options = []) {
        if ($this->val('throw', $options, $this->throwExceptions)) {
            $body = $response->getBody();
            if (is_array($body)) {
                $message = $this->val('message', $body, $response->getReasonPhrase());
            } else {
                $message = $response->getReasonPhrase();
            }

            if (!empty($body['errors']) && count($body['errors']) > 1) {
                $message .= ' '.implode(' ', array_column($body['errors'], 'message'));
            }

            throw new \Exception($message, $response->getStatusCode());
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
        $session->start($userID, false, false);

        return $this;
    }
}
