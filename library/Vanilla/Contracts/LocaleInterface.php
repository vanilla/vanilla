<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts;

/**
 * Interface for localization.
 */
interface LocaleInterface {
    /**
     * Translates a code into the selected locale's definition.
     *
     * @param string $code The code related to the language-specific definition.
     * @param string|boolean $default The default value to be displayed if the translation code is not found.
     * @return string
     */
    public function translate($code, $default = false);
}
