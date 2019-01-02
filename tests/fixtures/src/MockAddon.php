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

    /**
     * Constructor for MockAddon
     *
     * @param string $key
     */
    public function __construct(string $key) {
        $this->key = $key;
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
}
