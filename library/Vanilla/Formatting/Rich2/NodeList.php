<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2;

use Vanilla\Formatting\Formats\Rich2Format;
use Vanilla\Formatting\FormatText;
use Vanilla\Formatting\Rich2\Nodes\AbstractNode;
use Vanilla\Formatting\Rich2\Nodes\Text;
use Vanilla\Formatting\TextDOMInterface;

class NodeList implements \JsonSerializable, TextDOMInterface
{
    /** @var AbstractNode[] */
    protected array $nodes = [];

    /**
     * Constructor.
     *
     * @param AbstractNode ...$nodes
     */
    public function __construct(AbstractNode ...$nodes)
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }
    }

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

    /**
     * @inheritDoc
     */
    public function stringify(): FormatText
    {
        return new FormatText(json_encode($this), Rich2Format::FORMAT_KEY);
    }

    /**
     * @inheritDoc
     */
    public function getFragments(): array
    {
        $results = [];
        $this->getTextNodes($results);
        return $results;
    }

    /**
     * Get all the child text nodes.
     *
     * @param array $results
     */
    public function getTextNodes(array &$results): void
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof Text) {
                $results[] = $node;
            } else {
                $node->getTextNodes($results);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function renderHTML(): string
    {
        return $this->render();
    }
}
