<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
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
