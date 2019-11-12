<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;

/**
 * Item representing some JSON-LD item.
 */
abstract class AbstractJsonLDItem implements \JsonSerializable {

    /**
     * Calculate the value of the JSON-LD item.
     *
     * @return Data
     */
    abstract public function calculateValue(): Data;

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return $this->calculateValue();
    }
}
