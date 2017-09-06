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

        $headers = array_replace($this->defaultHeaders, $headers);
        $options = array_replace($this->defaultOptions, $options);

        $request = $this->container->getArgs(InternalRequest::class, [$method, $uri, $body, $headers, $options]);
        return $request;
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
     * Get the user ID that will be used to make requests.
     *
     * @return int Returns the userID.
     */
    public function getUserID() {
        return $this->userID;
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
