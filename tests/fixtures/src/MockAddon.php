<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts;

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

    public function getSubdir(): string {
        return $this->key;
    }

    public function getKey(): string {
        return $this->key;
    }
}
