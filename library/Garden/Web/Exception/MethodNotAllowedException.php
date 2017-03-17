<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web\Exception;

/**
 * An exception that represents a 405 method not allowed exception.
 */
class MethodNotAllowedException extends ClientException {

    /**
     * Initialize the {@link MethodNotAllowedException}.
     *
     * @param string $method The http method that's not allowed.
     * @param array|string $allow An array http methods that are allowed.
     * @param array $context An array of context variables that can be used to render a more detailed response.
     */
    public function __construct($method, $allow = [], array $context = []) {
        $allow = (array)$allow;
        $message = sprintf('%s not allowed.', strtoupper($method));
        parent::__construct($message, 405, ['HTTP_ALLOW' => strtoupper(implode(', ', $allow))] + $context);
    }

    /**
     * Get the allowed http methods.
     *
     * @return array Returns an array of allowed methods.
     */
    public function getAllow() {
        return array_map('trim', explode(',', $this->getContextItem('HTTP_ALLOW', '')));
    }
}
