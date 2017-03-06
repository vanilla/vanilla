<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
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
     * @param string $description A longer description for the error.
     */
    public function __construct($message = 'Page', $description = null) {
        if (strpos($message, ' ') === false) {
            $message = sprintf('%s not found.', $message);
        }

        parent::__construct($message, 404, ['description' => $description]);
    }
}
