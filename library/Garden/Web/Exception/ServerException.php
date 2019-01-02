<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web\Exception;

/**
 * Represents a 5xx server exception.
 */
class ServerException extends HttpException {

    /**
     * @inheritdoc
     */
    public function __construct($message, $code = 500, array $context = []) {
        parent::__construct($message, $code, $context);
    }
}
