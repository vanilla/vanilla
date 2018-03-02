<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
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
