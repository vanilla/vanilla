<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Web\Exception;

/**
 * An HTTP 403 forbidden exception.
 */
class ForbiddenException extends ClientException {
    /**
     * Construct a {@link ForbiddenException} object.
     *
     * @param string $message A custom message.
     * @param array $context Additional context for the message.
     */
    public function __construct($message = '', array $context = []) {
        parent::__construct($message, 403, $context);
    }
}
