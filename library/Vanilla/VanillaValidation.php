<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

use Garden\Schema\Validation;

/**
 * A validation object that translates its messages.
 */
class VanillaValidation extends Validation {
    /**
     * Translate a string.
     *
     * @param string $str The string to translate.
     * @return string Returns the string.
     */
    public function translate($str) {
        return t($str);
    }
}
