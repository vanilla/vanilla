<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts\Web\UASnifferInterface;

/**
 * Simple mock implementation of UA sniffing.
 */
class MockUASniffer implements UASnifferInterface {

    private $isIE11 = false;

    /**
     * Constructor.
     * @param bool $isIE11
     */
    public function __construct(bool $isIE11 = false) {
        $this->isIE11 = $isIE11;
    }

    /**
     * @return bool
     */
    public function isIE11(): bool {
        return $this->isIE11;
    }
}
