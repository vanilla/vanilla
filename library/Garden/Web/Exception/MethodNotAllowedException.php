<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web\Exception;

/**
 * An exception that represents a 405 method not allowed exception.
 */
class MethodNotAllowedException extends ClientException
{
    /**
     * Initialize the {@link MethodNotAllowedException}.
     *
     * @param string $method The http method that's not allowed.
     * @param array|string $allow An array http methods that are allowed.
     * @param array $context An array of context variables that can be used to render a more detailed response.
     * @param \Throwable|null $previous A previous throwable to add to the exception.
     */
    public function __construct($method, $allow = [], array $context = [], ?\Throwable $previous = null)
    {
        $allow = (array) $allow;
        if (!empty($method)) {
            $message = sprintf("%s not allowed.", strtoupper($method));
        } else {
            $message = "Method not allowed.";
        }
        parent::__construct($message, 405, ["method" => $method, "allow" => $allow] + $context, $previous);
    }

    /**
     * Get the allowed http methods.
     *
     * @return array Returns an array of allowed methods.
     */
    public function getAllow()
    {
        return $this->getContextItem("allow", "");
    }
}
