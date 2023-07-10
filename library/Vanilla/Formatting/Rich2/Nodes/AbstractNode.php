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

    public bool $getChildren = true;

    /**
     * Get the starting tag for the node.
     *
     * @return string
     */
    abstract protected function getHtmlStart(): string;

    /**
     * Get the ending tag for the node.
     *
     * @return string
     */
    abstract protected function getHtmlEnd(): string;

    /**
     * Check if the given node array is represented by the current AbstractNode implementation.
     *
     * @param array $node
     * @return bool
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && $node["type"] === static::getDefaultTypeName();
    }

    /**
     * AbstractNode constructor.
     *
     * @param array $data
     * @param string $parseMode
     */
    public function __construct(array $data, string $parseMode = Parser::PARSE_MODE_NORMAL)
    {
        unset($data["children"]);
        $this->data = $data;
        $this->children = new NodeList();
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
        return $this->getHtmlStart() . $this->children->render() . $this->getHtmlEnd();
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

    /**
     * Get child text nodes.
     *
     * @param array $results list of text nodes.
     */
    public function getTextNodes(array &$results)
    {
        if (!$this->children->isEmpty() && $this->getChildren) {
            $this->children->getTextNodes($results);
        }
    }

    /**
     * Adds $child to this node's children
     *
     * @param AbstractNode $child
     * @return bool
     */
    public function addChild(AbstractNode $child): bool
    {
        return $this->addChildWithProperWrapping($this, $child);
    }

    /**
     * Adds the child node to the parent node.
     * If the child node cannot be directly added as a child to the parent node, this method will recursively wrap
     * the child node in other nodes in a way that will allow it to be added as a descendant.
     *
     * @param AbstractNode $parent Parent node
     * @param AbstractNode $child Child node
     * @param array $visited Map of classes visited on the current branch. Used to prevent infinite recursion.
     * @return bool True if the child was successfully added, false if not.
     */
    private function addChildWithProperWrapping(AbstractNode $parent, AbstractNode $child, array $visited = []): bool
    {
        $parentClass = get_class($parent);
        if (isset($visited[$parentClass])) {
            // We need to bail out here if we have already visited this class to prevent infinite recursion.
            return false;
        }
        $visited[$parentClass] = true;
        if (!$parent->canHaveChild($child)) {
            $allowedChildClasses = $parent::getAllowedChildClasses();
            foreach ($allowedChildClasses as $allowedChildClass) {
                if (!is_a($allowedChildClass, AbstractNode::class, true)) {
                    continue;
                }
                $nextParent = $allowedChildClass::create();
                if ($this->addChildWithProperWrapping($nextParent, $child, $visited)) {
                    $parent->children->addNode($nextParent);
                    return true;
                }
            }
            return false;
        }

        $parent->children->addNode($child);
        return true;
    }

    /**
     * @param AbstractNode $node
     * @return bool
     */
    public function canHaveChild(AbstractNode $node): bool
    {
        return in_array(get_class($node), $this::getAllowedChildClasses(), true);
    }

    /**
     * Returns an array of valid AbstractNode class names that can be children of this node.
     *
     * @return class-string<AbstractNode>[]
     */
    abstract public static function getAllowedChildClasses(): array;

    /**
     * Get a default `type` value for this node used for node creation.
     *
     * @return string
     */
    abstract public static function getDefaultTypeName(): string;

    /**
     * @return AbstractNode
     */
    public static function create(): AbstractNode
    {
        return new static([
            "type" => static::getDefaultTypeName(),
        ]);
    }
}
