<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Contracts;

/**
 * Interface for reading configuration values.
 */
interface ConfigurationInterface {
    /**
     * Gets a setting from the configuration array. Returns $defaultValue if the value isn't found.
     *
     * @param string $key The name of the configuration setting to get.
     * @param mixed $defaultValue If the parameter is not found in the group, this value will be returned.
     * @return mixed The configuration value.
     */
    public function get($key, $defaultValue = false);

    /**
     * Save a value to the configuration.
     *
     * @param string|array $name A config key. Dot notation supported.
     * @param string $value The value to save.
     * @param array|false $options Some options on how to save it. Pass false to do an "in-memory" save.
     * @return bool|int
     */
    public function saveToConfig($name, $value = '', $options = []);
}
