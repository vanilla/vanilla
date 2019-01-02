<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

/**
 * For internal use.
 */
class ShortCircuitException extends \Exception {
    /**
     * ShortCircuitException constructor.
     */
    public function __construct() {
        parent::__construct('Short Circuit', 500);
    }
}
