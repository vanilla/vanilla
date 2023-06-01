<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Garden\Git\Head;

class RootNode extends AbstractNode
{
    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedParentNodes(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedChildClasses(): array
    {
        return [
            Paragraph::class,
            Blockquote::class,
            CodeBlock::class,
            Heading::class,
            UnorderedList::class,
            OrderedList::class,
            External::class,
            Spoiler::class,
            Table::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function isAllowedAtRoot(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultTypeName(): string
    {
        return "root";
    }

    public function getNodeList(): \Vanilla\Formatting\Rich2\NodeList
    {
        return $this->children;
    }
}
