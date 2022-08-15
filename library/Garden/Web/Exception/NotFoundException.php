<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web\Exception;

/**
 * Represents a 404 not found error.
 */
class NotFoundException extends ClientException
{
    /**
     * Initialize a {@link NotFoundException}.
     *
     * @param string $message The error message or a one word resource name.
     * @param array $context An array of context variables that can be used to render a more detailed response.
     * @param \Throwable|null $previous A previous throwable to add to the exception.
     */
    public function __construct($message = "Page", array $context = [], ?\Throwable $previous = null)
    {
        if (!empty($message) && strpos($message, " ") === false) {
            $message = sprintf(t("%s not found."), $message);
        }

        parent::__construct($message, 404, $context, $previous);
    }
}
