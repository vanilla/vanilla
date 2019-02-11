<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Navigation;

use Throwable;

/**
 * Exception for when a breadcrumb provider could not be found.
 */
class BreadcrumbProviderNotFoundException extends \Exception {

    /**
     * @inheritdoc
     */
    public function __construct(string $recordType, int $code = 0, Throwable $previous = null) {
        parent::__construct("$recordType breadcrumb provider could not be found.", $code, $previous);
    }
}
