<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts;

class MockCacheBusterInterface implements Contracts\Web\CacheBusterInterface {

    private $value;

    /**
     *
     */
    public function __construct(string $value = "") {
        $this->value = $value;
    }

    public function value(): string {
        return $this->value;
    }
}
