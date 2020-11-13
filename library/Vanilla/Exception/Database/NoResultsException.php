<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Exception\Database;

use Garden\Web\Exception\NotFoundException;

/**
 * An exception to be thrown when results are expected, but not actually received.
 */
class NoResultsException extends NotFoundException {
    /**
     * NoResultsException constructor.
     *
     * @param string $message
     */
    public function __construct($message = 'Results') {
        parent::__construct($message);
    }
}
