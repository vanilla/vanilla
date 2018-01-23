<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web\Exception;

/**
 * Represents a 404 not found error.
 */
class NotFoundException extends ClientException {
    /**
     * Initialize a {@link NotFoundException}.
     *
     * @param string $message The error message or a one word resource name.
     * @param array $context An array of context variables that can be used to render a more detailed response.
     */
    public function __construct($message = 'Page', array $context = []) {
        if (!empty($message) && strpos($message, ' ') === false) {
            $message = sprintf('%s not found.', $message);
        }

        parent::__construct($message, 404, $context);
    }
}
