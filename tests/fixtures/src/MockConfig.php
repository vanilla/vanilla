<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts;

/**
 * Mock configuration object. Implements simple set/get for the ConfigurationInterface.
 */
class MockConfig implements Contracts\ConfigurationInterface {

    /** @var array A mapping of config key to value */
    private $data = [];

    /**
     * @inheritdoc
     */
    public function get(string $key, $defaultValue = false) {
        return $this->data[$key] ?? $defaultValue;
    }

    /**
     * Set a mock config value.
     *
     * @param string $key The key to save as.
     * @param mixed $value The value to lookup.
     *
     * @return $this For fluent chaining.
     */
    public function set(string $key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
}
