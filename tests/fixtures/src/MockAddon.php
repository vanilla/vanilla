<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts;

/**
 * Mock addon class. Assumes key and subdirectory are the same.
 */
class MockAddon implements Contracts\AddonInterface {

    /** @var string */
    private $key;

    /** @var array */
    private $info;

    /**
     * Constructor for MockAddon
     *
     * @param string $key
     * @param array $info
     */
    public function __construct(string $key, array $info = []) {
        $this->key = $key;
        $this->info = $info;
    }

    /**
     * @return string
     */
    public function getSubdir(): string {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * @inheritdoc
     */
    public function getInfo(): array {
        return $this->info;
    }

    /**
     * @inheritdoc
     */
    public function getInfoValue(string $key, $default = null) {
        return isset($this->info[$key]) ? $this->info[$key] : $default;
    }
}
