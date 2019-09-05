<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Exception\ClientException;
use Garden\Web\RequestInterface;

/**
 * Class for validating various things about a request.
 */
class RequestValidator {

    /** @var RequestInterface */
    private $request;

    /**
     * DI.
     *
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request) {
        $this->request = $request;
    }

    /**
     * Ensure the request is not of a particular type.
     *
     * @param string $requestMethod The method of the request, Eg. POST, PATCH, GET. See Gdn_Request constants.
     * @param string $extraMessage Additional message content to add to the exception.
     *
     * @throws ClientException If the request method doesn't match.
     *
     */
    public function blockRequestType(string $requestMethod, string $extraMessage = "") {
        $message = "Request method $requestMethod is not allowed.";
        if ($extraMessage !== "") {
            $message .= '\n' . $extraMessage;
        }

        if ($this->request->getMethod() === $requestMethod) {
            throw new ClientException($message, 400);
        }
    }
}
