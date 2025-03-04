<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Garden\Git\Head;
use Vanilla\Formatting\Rich2\NodeList;

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
    public static function getDefaultTypeName(): string
    {
        return "root";
    }

    public function getNodeList(): \Vanilla\Formatting\Rich2\NodeList
    {
        $nodes = $this->children->getNodes();
        if (empty($nodes)) {
            return $this->children;
        }

        return new NodeList(...$this->trimEmptyTrailingNodes($nodes));
    }

    /**
     * Given a children of the root node ensure that any empty trailing nodes are removed.
     *
     * @param array<AbstractNode> $nodes
     * @return array
     */
    private function trimEmptyTrailingNodes(array $nodes): array
    {
        $lastIndex = count($nodes) - 1;
        $lastNode = $nodes[$lastIndex] ?? null;
        if (!$lastNode instanceof Paragraph) {
            return $nodes;
        }

        $lastNodeChildren = $lastNode->children->getNodes();
        if (count($lastNodeChildren) > 1) {
            return $nodes;
        }

        // The one child must be only text.
        if (!$lastNodeChildren[0] instanceof Text || !empty(trim($lastNodeChildren[0]->renderText()))) {
            return $nodes;
        }

        unset($nodes[$lastIndex]);
        return $this->trimEmptyTrailingNodes($nodes);
    }
}
