<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web\Exception;

use Garden\Http\HttpResponse;
use Garden\Web\Data;

/**
 * An exception that contains a response.
 *
 * Throw this exception when you are deep inside a controller call chain and need to render a response. This method isn't
 * meant to be heavily used except for refactoring purposes. In general you should strive to return responses rather
 * than throw an exception. However, if you are in old code that just can't return a response then throw an exception
 * in order to make your code somewhat testable.
 */
class ResponseException extends \Exception {
    /**
     * @var Data
     */
    private $response;

    /**
     * ResponseException constructor.
     *
     * @param Data $response
     */
    public function __construct(Data $response) {
        parent::__construct(HttpResponse::reasonPhrase($response->getStatus()) ?? 'Response', $response->getStatus());
        $this->response = $response;
    }

    /**
     * Get the response associated with this exception.
     *
     * @return Data
     */
    public function getResponse(): Data {
        return $this->response;
    }
}
