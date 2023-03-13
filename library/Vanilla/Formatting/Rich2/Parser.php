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
use Vanilla\Formatting\Rich2\Nodes\ListItem;
use Vanilla\Formatting\Rich2\Nodes\ListItemChild;
use Vanilla\Formatting\Rich2\Nodes\ListNode;
use Vanilla\Formatting\Rich2\Nodes\Mention;
use Vanilla\Formatting\Rich2\Nodes\Paragraph;
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

class Parser
{
    const PARSE_MODE_EXTENDED = "extended";
    const PARSE_MODE_NORMAL = "normal";
    const PARSE_MODE_QUOTE = "quote";

    protected array $nodeClasses = [
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
        Text::class,
        External::class,
        Mention::class,
        Blockquote::class,
        BlockquoteLine::class,
        Spoiler::class,
        SpoilerContent::class,
        SpoilerLine::class,
        ListNode::class,
        ListItem::class,
        ListItemChild::class,
    ];

    /** @var int[] */
    protected array $slugCounter;

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
    ): AbstractNode {
        $children = $node["children"] ?? [];
        $children = $this->parseNodeList($children, $callback, $parseMode);

        foreach ($this->nodeClasses as $class) {
            if (is_subclass_of($class, AbstractNode::class) && $class::matches($node)) {
                /**
                 * @psalm-suppress UndefinedClass
                 */
                $nodeObject = new $class($node, $children, $parseMode);
                break;
            }
        }
        if (!isset($nodeObject)) {
            $nodeObject = new Blank($node, $children, $parseMode);
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
        $nodeList = new NodeList();
        foreach ($nodes as $node) {
            $nodeList->addNode($this->parseNode($node, $callback, $parseMode));
        }
        return $nodeList;
    }
}
