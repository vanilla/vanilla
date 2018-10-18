<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Config;

interface ConfigInterface {
    public function get(string $key, $default = false);
}
