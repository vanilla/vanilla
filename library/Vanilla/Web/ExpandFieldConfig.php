<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Simple data class for describing the config of an expand field.
 */
final class ExpandFieldConfig {

    /** @var mixed */
    private $default;

    /** @var array */
    private $expandFields;

    /** @var callable */
    private $fetch;

    /**
     * Set the config.
     *
     * @param array $expandFields An associative array, mapping destination keys to the keys associated with their IDs.
     * @param callable $fetch Used to fetch records that will be expanded.
     * @param mixed $default Default value to use when a suitable record cannot be fetched.
     */
    public function __construct(array $expandFields, callable $fetch, $default = null) {
        $this->expandFields = $expandFields;
        $this->fetch = $fetch;
        $this->default = $default;
    }

    /**
     * Get records by ID, using the configured lookup function.
     *
     * @return callable
     */
    public function getFetch(): callable {
        return $this->fetch;
    }

    /**
     * Get default value configured for this field.
     *
     * @return mixed
     */
    public function getDefault() {
        return $this->default;
    }

    /**
     * Get the field configuration.
     *
     * @return array
     */
    public function getFields(): array {
        return $this->expandFields;
    }

    /**
     * Given an expand field target, return its associated record ID field.
     *
     * @param string $destination
     * @return string|null
     */
    public function getFieldByDestination(string $destination): ?string {
        return $this->expandFields[$destination] ?? null;
    }
}
