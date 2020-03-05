<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * A test event for PSR event tests.
 */
class TestEvent implements StoppableEventInterface {

    private $stopPropagation = false;

    private $num = 0;

    /**
     * Increment the number.
     */
    public function incNum() {
        $this->num++;
    }

    /**
     * Get the number.
     *
     * @return int
     */
    public function getNum(): int {
        return $this->num;
    }

    /**
     * @inheritDoc
     */
    public function isPropagationStopped(): bool {
        return $this->stopPropagation;
    }

    /**
     * Set the stop propagation property.
     */
    public function stopPropagation() {
        $this->stopPropagation = true;
    }
}
