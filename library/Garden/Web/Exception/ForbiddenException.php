<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Web\Exception;

/**
 * An HTTP 403 forbidden exception.
 */
class ForbiddenException extends ClientException
{
    /**
     * Construct a {@link ForbiddenException} object.
     *
     * @param string $message A custom message.
     * @param array $context Additional context for the message.
     * @param \Throwable $previous A previous throwable to add to the exception.
     */
    public function __construct($message = "", array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $context, $previous);
    }
}
