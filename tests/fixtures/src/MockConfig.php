<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts;

/**
 * Mock configuration object. Implements simple set/get for the ConfigurationInterface.
 */
class MockConfig implements Contracts\ConfigurationInterface {

    const DEFAULT_CONFIG = [
        'Garden.RewriteUrls' => true,
        'Garden.AllowSSL' => true,
    ];

    /** @var array A mapping of config key to value */
    private $data;

    /**
     * Construct a mock configuration with some default values.
     *
     * @param array $data
     */
    public function __construct(array $data = []) {
        $this->data = static::DEFAULT_CONFIG;
        $this->data += $this->flattenArray($data);
    }


    /**
     * @inheritdoc
     */
    public function get($key, $defaultValue = false) {
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

    /**
     * Flatten and set the data into configuration.
     *
     * @param array $data
     */
    public function loadData(array $data) {
        $data = $this->flattenArray($data);
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Clear all config data.
     */
    public function reset() {
        $this->data = static::DEFAULT_CONFIG;
    }

    /**
     * Flatten an array by concating it's strings.
     *
     * @example
     * $before = ['Top' => ['Middle' => true]]
     * $after = flattenArray($before);
     * // ['Top.Middle' => true]
     *
     * @param array $array
     * @param string $prefix
     * @return array
     */
    private function flattenArray(array $array, string $prefix = '') {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && $this->isAssosciativeArray($value)) {
                $result = $result + $this->flattenArray($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }

    /**
     * Quickly check if we have an indexed or sequential array.
     *
     * @param array $arr
     *
     * @return bool
     */
    private function isAssosciativeArray(array $arr): bool {
        return count(array_filter(array_keys($arr), 'is_string')) > 0;
    }
}
