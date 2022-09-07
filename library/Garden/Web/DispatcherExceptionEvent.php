<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Web;

use Exception;
use Gdn_Request;
use Throwable;

/**
 * Represent a Dispatcher Exception Event.
 */
class DispatcherExceptionEvent
{
    /** @var Exception|Throwable */
    private $exception;

    /** @var Gdn_Request */
    private $request;

    /**
     *  DispatcherExceptionEvent Constructor.
     *
     * @param $exception
     * @param $request
     */
    public function __construct($exception, $request)
    {
        $this->exception = $exception;
        $this->request = $request;
    }

    /**
     * @return Exception|Throwable
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return Gdn_Request
     */
    public function getRequest(): Gdn_Request
    {
        return $this->request;
    }
}
