<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web\Exception;

/**
 * Represents a 400 series exception.
 */
class ClientException extends HttpException
{
    /**
     * Initialize an instance of the {@link ClientException} class.
     *
     * The 4xx class of status code is intended for cases in which the client seems to have erred.
     * When constructing a client exception you can pass additional information on the {@link $context} parameter
     * to aid in rendering.
     *
     * - Keys beginning with **HTTP_** will be added as headers.
     * - **description** will give the exception a longer description.
     *
     * @param string $message The error message.
     * @param int $code The http error code.
     * @param array $context An array of context variables that can be used to render a more detailed response.
     * @param \Throwable|null $previous A previous throwable to add to the exception.
     */
    public function __construct($message = "", $code = 400, array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $context, $previous);
    }
}
