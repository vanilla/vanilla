<?php
/**
 * Gdn_SanitizedUserException
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

/**
 * A wrapper for the UserException class so that methods can throw a specific
 * application as a means of validation or user error, rather than a critical exception.
 */
class Gdn_SanitizedUserException extends Gdn_UserException {
    /**
     * Constructs the Gdn_ApplicationException.
     *
     * @param string $message A user readable message for the exception.
     * @param int $code The error code.
     * @param Exception $previous The previous exception used for exception chaining.
     */
    public function __construct($message, $code = 400, $previous = null) {
        parent::__construct($message, (int)$code, $previous);
    }
}
