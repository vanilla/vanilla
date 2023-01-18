<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

class Heading extends AbstractNode
{
    /**
     * @inheritDoc
     */
    public function getFormatString(): string
    {
        $level = $this->getLevel();
        return "<h$level>%s</h$level>";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && in_array($node["type"], ["h2", "h3", "h4", "h5"]);
    }

    /**
     * Get heading level (2 - 5)
     * @return int|null
     */
    public function getLevel(): ?int
    {
        return (int) substr($this->data["type"], 1);
    }
}
