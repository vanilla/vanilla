<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts\LocaleInterface;

/**
 * Mock locale object. Passes all strings through untouched.
 */
class MockLocale extends MockConfig implements LocaleInterface {

    const DEFAULT_CONFIG = [];

    /**
     * Return the value directly.
     *
     * @param string $code
     * @param bool|string $default
     * @return string
     */
    public function translate($code, $default = false) {
        $setValue = self::get($code, false);
        if ($setValue) {
            return $setValue;
        } elseif ($default) {
            return $default;
        } else {
            return $code;
        }
    }
}
