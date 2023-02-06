<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class Blank extends AbstractNode
{
    /**
     * @inheritDoc
     */
    public function getFormatString(): string
    {
        return "%s";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return true;
    }
}
