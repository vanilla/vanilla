<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts;

class MockConfig implements Contracts\ConfigurationInterface {

    /** @var array A mapping of config key to value */
    private $data = [];

    public function get(string $key, $defaultValue = false) {
        return $this->data[$key] ?? $defaultValue;
    }

    /**
     * @param string $key
     * @param $value
     *
     * @return $this For fluent chaining.
     */
    public function set(string $key, $value) {
        $this->data[$key] = $value;
        return $this;
    }

}
