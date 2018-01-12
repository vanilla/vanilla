<?php
/**
 * Gdn_UserException
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * A wrapper for the Exception class so that methods can throw a specific
 * application as a means of validation or user error, rather than a critical exception.
 */
class Gdn_UserException extends Exception {

    /**
     * Constructs the Gdn_ApplicationException.
     *
     * @param string $message A user readable message for the exception.
     * @param Exception $previous The previous exception used for exception chaining.
     */
    public function __construct($message, $code = 400, $previous = null) {
        parent::__construct($message, (int)$code, $previous);
    }
}
