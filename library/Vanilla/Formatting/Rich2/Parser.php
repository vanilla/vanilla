<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2;

use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Rich2\Nodes\AbstractNode;
use Vanilla\Formatting\Rich2\Nodes\Anchor;
use Vanilla\Formatting\Rich2\Nodes\Blank;
use Vanilla\Formatting\Rich2\Nodes\Blockquote;
use Vanilla\Formatting\Rich2\Nodes\BlockquoteLine;
use Vanilla\Formatting\Rich2\Nodes\CodeBlock;
use Vanilla\Formatting\Rich2\Nodes\CodeLine;
use Vanilla\Formatting\Rich2\Nodes\External;
use Vanilla\Formatting\Rich2\Nodes\Heading;
use Vanilla\Formatting\Rich2\Nodes\LegacyEmojiImage;
use Vanilla\Formatting\Rich2\Nodes\ListItem;
use Vanilla\Formatting\Rich2\Nodes\ListItemChild;
use Vanilla\Formatting\Rich2\Nodes\Mention;
use Vanilla\Formatting\Rich2\Nodes\OrderedList;
use Vanilla\Formatting\Rich2\Nodes\Paragraph;
use Vanilla\Formatting\Rich2\Nodes\RootNode;
use Vanilla\Formatting\Rich2\Nodes\Spoiler;
use Vanilla\Formatting\Rich2\Nodes\SpoilerContent;
use Vanilla\Formatting\Rich2\Nodes\SpoilerLine;
use Vanilla\Formatting\Rich2\Nodes\Table;
use Vanilla\Formatting\Rich2\Nodes\TableBody;
use Vanilla\Formatting\Rich2\Nodes\TableHead;
use Vanilla\Formatting\Rich2\Nodes\TableFoot;
use Vanilla\Formatting\Rich2\Nodes\TableCaption;
use Vanilla\Formatting\Rich2\Nodes\TableColumn;
use Vanilla\Formatting\Rich2\Nodes\TableHeader;
use Vanilla\Formatting\Rich2\Nodes\TableRow;
use Vanilla\Formatting\Rich2\Nodes\Text;
use Vanilla\Formatting\Rich2\Nodes\UnorderedList;

class Parser
{
    const PARSE_MODE_EXTENDED = "extended";
    const PARSE_MODE_NORMAL = "normal";
    const PARSE_MODE_QUOTE = "quote";

    protected array $nodeClasses = [
        Text::class,
        Anchor::class,
        CodeBlock::class,
        CodeLine::class,
        Heading::class,
        Paragraph::class,
        Table::class,
        TableBody::class,
        TableHead::class,
        TableFoot::class,
        TableCaption::class,
        TableColumn::class,
        TableHeader::class,
        TableRow::class,
        External::class,
        Mention::class,
        Blockquote::class,
        BlockquoteLine::class,
        Spoiler::class,
        SpoilerContent::class,
        SpoilerLine::class,
        UnorderedList::class,
        OrderedList::class,
        ListItem::class,
        ListItemChild::class,
        LegacyEmojiImage::class,
    ];

    /** @var int[] */
    protected array $slugCounter;

    protected array $exclusiveChildTypeMap = [];

    protected array $exclusiveParentTypeMap = [];

    protected array $exclusiveAncestorTypeMap = [];

    public function __construct()
    {
        $this->buildTypeLookupTables();
    }

    /**
     * Builds some useful look-up tables for the parser. These tables include:
     * - $exclusiveChildTypeMap: Mapping between node types and their required parent types.
     * - $exclusiveParentTypeMap: Mapping between node types and their required child types.
     * - $exclusiveAncestorTypeMap: Mapping between node types which have required parent types
     *   and their ancestors which don't have any required parents.
     *
     * @return void
     */
    private function buildTypeLookupTables(): void
    {
        // Build a mapping of exclusive relationships between different node types.
        foreach ($this->nodeClasses as $nodeClass) {
            if (is_a($nodeClass, AbstractNode::class, true)) {
                $exclusiveChildTypes = $nodeClass::getExclusiveChildTypes();
                $this->exclusiveChildTypeMap[$nodeClass] = $exclusiveChildTypes;
                if (is_array($exclusiveChildTypes)) {
                    foreach ($exclusiveChildTypes as $exclusiveChildType) {
                        if (!isset($this->exclusiveParentTypeMap[$exclusiveChildType])) {
                            $this->exclusiveParentTypeMap[$exclusiveChildType] = [];
                        }
                        $this->exclusiveParentTypeMap[$exclusiveChildType][] = $nodeClass;
                    }
                }
            }
        }
        foreach (array_keys($this->exclusiveParentTypeMap) as $childType) {
            $this->exclusiveAncestorTypeMap[$childType] = $this->resolveAncestor($childType);
        }
    }

    /**
     * Get ancestors for node types with required parent types.
     *
     * @param string $type
     * @return string|null
     */
    private function resolveAncestor(string $type): ?string
    {
        $types = $this->exclusiveParentTypeMap[$type] ?? null;

        if (is_null($types)) {
            return $type;
        }

        foreach ($types as $type) {
            $nextType = $this->resolveAncestor($type);
            if (isset($nextType)) {
                return $nextType;
            }
        }

        return null;
    }

    /**
     * Parses the given json and returns a NodeList data structure
     *
     * @param string $content
     * @param callable|null $callback
     * @param string $parseMode
     * @return NodeList
     * @throws FormattingException
     */
    public function parse(
        string $content,
        ?callable $callback = null,
        string $parseMode = self::PARSE_MODE_NORMAL
    ): NodeList {
        if (empty($content)) {
            // Render an empty paragraph if we don't have content for places where empty is allowed.
            return new NodeList(Paragraph::create());
        }
        $nodes = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($nodes)) {
            throw new FormattingException("JSON could not be decoded.\n $content");
        }

        $this->slugCounter = [];

        return $this->parseNodeList($nodes, $callback, $parseMode);
    }

    /**
     * Internal method for parsing a node array and returning a corresponding AbstractNode object
     *
     * @param array $node
     * @param callable|null $callback
     * @param string $parseMode
     * @return AbstractNode
     */
    protected function parseNode(
        array $node,
        ?callable $callback = null,
        string $parseMode = self::PARSE_MODE_NORMAL
    ): ?AbstractNode {
        foreach ($this->nodeClasses as $class) {
            if (is_subclass_of($class, AbstractNode::class) && $class::matches($node)) {
                /**
                 * @psalm-suppress UndefinedClass
                 */
                $nodeObject = new $class($node, $parseMode);
                break;
            }
        }
        if (!isset($nodeObject)) {
            $nodeObject = new Blank($node, $parseMode);
        }

        $children = $node["children"] ?? [];
        foreach ($children as $child) {
            $childNode = $this->parseNode($child, $callback, $parseMode);
            $this->tryAddChild($nodeObject, $childNode);
        }

        if (isset($callback)) {
            $callback($nodeObject);
        }

        // Need to generate refs for headings
        if ($nodeObject instanceof Heading && !$nodeObject->hasRef()) {
            $slug = slugify($nodeObject->renderText());
            $count = $this->slugCounter[$slug] ?? 0;
            $this->slugCounter[$slug] = $count + 1;
            $slug .= $count > 0 ? "-$count" : "";
            $nodeObject->setRef($slug);
        }
        return $nodeObject;
    }

    /**
     * Internal method for parsing an array of nodes and returning a NodeList object
     *
     * @param array $nodes
     * @param callable|null $callback
     * @param string $parseMode
     * @return NodeList
     */
    protected function parseNodeList(
        array $nodes,
        ?callable $callback = null,
        string $parseMode = self::PARSE_MODE_NORMAL
    ): NodeList {
        $rootNode = new RootNode(["type" => "root"]);
        foreach ($nodes as $node) {
            $node = $this->parseNode($node, $callback, $parseMode);
            $this->tryAddChild($rootNode, $node);
        }
        return $rootNode->getNodeList();
    }

    /**
     * Adds the child node to the parent node.
     *
     * If the child node cannot be directly added as a child to the parent node, this method will recursively wrap
     * the child node in other nodes in a way that will allow it to be added as a descendant.
     *
     * @param AbstractNode $parent Parent node
     * @param AbstractNode $child Child node
     * @param array $visited Map of classes visited on the current branch. Used to prevent infinite recursion.
     * @return bool True if the child was successfully added, false if not.
     */
    private function tryAddChild(AbstractNode $parent, AbstractNode $child, array $visited = []): bool
    {
        $parentClass = get_class($parent);
        if (isset($visited[$parentClass])) {
            // Prevent infinite recursion if we already visited this class.
            return false;
        }
        $visited[$parentClass] = true;

        $requiredWrappingType = $this->getRequiredWrappingType($parent, $child);

        if (is_null($requiredWrappingType)) {
            $parent->addChild($child);
            return true;
        }

        if (is_a($requiredWrappingType, AbstractNode::class, true)) {
            $nextParent = $requiredWrappingType::create();
            if ($this->tryAddChild($nextParent, $child, $visited)) {
                $parent->addChild($nextParent);
                return true;
            }
        }
        return false;
    }

    /**
     * This evaluates two nodes and returns a node type if it's required in order to add
     * the $child node to the $parent node. It returns null if there are no required types.
     *
     * @param AbstractNode $parent
     * @param AbstractNode $child
     * @return array|null
     */
    private function getRequiredWrappingType(AbstractNode $parent, AbstractNode $child): ?string
    {
        $childClass = get_class($child);
        $parentClass = get_class($parent);
        $exclusiveChildTypes = $this->exclusiveChildTypeMap[$parentClass] ?? null;
        $exclusiveChildParentTypes = $this->exclusiveParentTypeMap[$childClass] ?? null;

        // First check if the child is one of the parent's required child types. If not return the required type.
        if (isset($exclusiveChildTypes) && !in_array($childClass, $exclusiveChildTypes)) {
            return array_shift($exclusiveChildTypes);
        }

        // Now check if the parent is one of the child's required parent types.
        // If not, return a valid type which doesn't have any required parent types.
        if (isset($exclusiveChildParentTypes) && !in_array($parentClass, $exclusiveChildParentTypes)) {
            return $this->exclusiveAncestorTypeMap[$childClass] ?? null;
        }

        return null;
    }
}
