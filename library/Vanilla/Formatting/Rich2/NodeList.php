<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2;

use Vanilla\Formatting\Rich2\Nodes\AbstractNode;

class NodeList implements \JsonSerializable
{
    /** @var AbstractNode[] */
    protected array $nodes = [];

    /**
     * Add a node to the list.
     *
     * @param AbstractNode $node
     * @return void
     */
    public function addNode(AbstractNode $node)
    {
        $this->nodes[] = $node;
    }

    /**
     * Returns an HTML representation of the list of nodes and their descendants.
     *
     * @return string
     */
    public function render(): string
    {
        $output = "";
        foreach ($this->nodes as $node) {
            $output .= $node->render();
        }
        return $output;
    }

    /**
     * Returns an **un-encoded** plain text representation of the list of nodes and their descendants.
     *
     * @return string
     */
    public function renderText(): string
    {
        $output = "";
        foreach ($this->nodes as $node) {
            $output .= $node->renderText();
        }
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->nodes;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->nodes);
    }
}
