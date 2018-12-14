<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts;

class MockCacheBuster implements Contracts\Web\CacheBusterInterface {

    private $value;

    /**
     *
     * @param $value
     */
    public function __construct($value = "") {
        $this->value = $value;
    }

    public function value(): string {
        return $this->value;
    }
}
