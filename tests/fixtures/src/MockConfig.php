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

    /** @var array A mapping of config key to value */
    private $data = [
        'Garden.RewriteUrls' => true,
        'Garden.AllowSSL' => true,
    ];

    /**
     * Construct a mock configuration with some default values.
     *
     * @param array $data
     */
    public function __construct(array $data = []) {
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

    public function loadData(array $data) {
        $data = $this->flattenArray($data);
        $this->data += $data;
    }

    /**
     * Flatten an array by concating it's strings.
     *
     * @example
     * $before = ['Top' => ['Middle' => true]]
     * $after = flattenArray($before);
     * // ['Top.Middle' => true]
     *
     * @param $array
     * @param string $prefix
     * @return array
     */
    private function flattenArray($array, $prefix = '') {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flattenArray($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }
}
