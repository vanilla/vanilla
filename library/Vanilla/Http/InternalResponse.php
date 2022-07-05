<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Http;

use Garden\Http\HttpResponse;

/**
 * Response type for internal requests.
 */
class InternalResponse extends HttpResponse
{
    /** @var \Throwable|null */
    private $throwable;

    /**
     * @return \Throwable|null
     */
    public function getThrowable(): ?\Throwable
    {
        return $this->throwable;
    }

    /**
     * @param \Throwable|null $exception
     */
    public function setThrowable(?\Throwable $exception): void
    {
        $this->throwable = $exception;
    }
}
