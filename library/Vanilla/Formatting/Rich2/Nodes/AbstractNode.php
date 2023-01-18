<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Formatting\Rich2\NodeList;
use Vanilla\Formatting\Rich2\Parser;

abstract class AbstractNode implements \JsonSerializable
{
    protected NodeList $children;

    protected array $data = [];

    protected string $parseMode;

    /**
     * Get the format string which is passed to sprintf to render the HTML for the node.
     *
     * @return string
     */
    abstract protected function getFormatString(): string;

    /**
     * Check if the given node array is represented by the current AbstractNode implementation.
     *
     * @param array $node
     * @return bool
     */
    abstract public static function matches(array $node): bool;

    /**
     * AbstractNode constructor.
     *
     * @param array $data
     * @param NodeList $children
     */
    public function __construct(array $data, NodeList $children, string $parseMode = Parser::PARSE_MODE_NORMAL)
    {
        unset($data["children"]);
        $this->data = $data;
        $this->children = $children;
        $this->parseMode = $parseMode;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        if (!$this->children->isEmpty()) {
            $this->data["children"] = $this->children;
        }
        return $this->data;
    }

    /**
     * Returns an HTML representation of the current node and its descendants.
     *
     * @return string
     */
    public function render(): string
    {
        return sprintf($this->getFormatString(), $this->children->render());
    }

    /**
     * Returns an **un-encoded** plain text representation of the current node and its descendants.
     *
     * @return string
     */
    public function renderText(): string
    {
        return $this->getTextStart() . $this->children->renderText() . $this->getTextEnd();
    }

    /**
     * Returns a string which is prepended to plain text output. This is made to be overridden by subclasses.
     *
     * @return string
     */
    protected function getTextStart(): string
    {
        return "";
    }

    /**
     * Returns a string which is appended to plain text output. This is made to be overridden by subclasses.
     *
     * @return string
     */
    protected function getTextEnd(): string
    {
        return "";
    }
}
