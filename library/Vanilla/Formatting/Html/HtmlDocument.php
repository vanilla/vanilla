<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html;

use Vanilla\Formatting\Formats\WysiwygFormat;
use Vanilla\Formatting\FormatText;
use Vanilla\Formatting\Html\Processor\HtmlProcessorTrait;
use Vanilla\Formatting\HtmlDomAttributeFragment;
use Vanilla\Formatting\HtmlDomRangeFragment;
use Vanilla\Formatting\TextDOMInterface;
use Vanilla\Formatting\TextFragmentInterface;

/**
 * Class for parsing and modifying HTML.
 */
class HtmlDocument implements TextDOMInterface
{
    use HtmlProcessorTrait;

    /**
     * @var string[]
     */
    const FRAGMENT_ATTRIBUTES = ["alt", "title"];

    /** @var string[]  */
    private const TAG_INLINE_TEXT = [
        "a",
        "abbr",
        "acronym",
        "b",
        "bdo",
        "big",
        "br",
        "cite",
        "code",
        "dfn",
        "em",
        "i",
        "kbd",
        "q",
        "samp",
        "small",
        "strong",
        "sub",
        "sup",
        "time",
        "tt",
        "var",
    ];

    /**
     * @var string[]
     */
    private const TAG_STOP = ["iframe", "object", "head", "video", "style", "img", "pre"];

    /** @var \DOMDocument */
    private $dom;

    /** @var bool */
    private $wrap;

    /**
     * Constructor.
     *
     * @param string $innerHtml HTML to construct the DOM with.
     * @param bool $wrap Whether or not to wrap in our own fragment prefix/suffix.
     */
    public function __construct(string $innerHtml, bool $wrap = true)
    {
        $this->dom = new \DOMDocument("1.0", "UTF-8");
        $this->wrap = $wrap;

        // DomDocument will automatically add html, head and body wrapper if we don't.
        // We add our own to ensure consistency.
        if ($wrap) {
            $innerHtml = $this->getDocumentPrefix() . $innerHtml . $this->getDocumentSuffix();
        }
        @$this->dom->loadHTML($innerHtml, LIBXML_NOBLANKS);
    }

    /**
     * @return HtmlDocument
     */
    protected function getDocument(): HtmlDocument
    {
        return $this;
    }

    /**
     * Query the DOM with some xpath.
     *
     * @param string $xpathQuery
     * @see https://devhints.io/xpath For a cheatsheet.
     *
     * @return \DOMNodeList
     */
    public function queryXPath(string $xpathQuery)
    {
        $xpath = new \DOMXPath($this->getDom());
        return $xpath->query($xpathQuery) ?: new \DOMNodeList();
    }

    /**
     * Get the document.
     *
     * @return \DOMDocument
     */
    public function getDom(): \DOMDocument
    {
        return $this->dom;
    }

    /**
     * Get the root element of the DOM.
     *
     * @return \DOMElement
     */
    public function getRoot(): \DOMElement
    {
        if ($this->wrap) {
            return $this->dom->getElementsByTagName("body")->item(0);
        } else {
            return $this->dom->parentNode;
        }
    }

    /**
     * Return the inner HTML content of the document.
     * We grab everything inside the document body.
     *
     * @return string
     */
    public function getInnerHtml(): string
    {
        if ($this->wrap) {
            $content = $this->dom->getElementsByTagName("body");
            $result = @$this->dom->saveXML($content[0], LIBXML_NOEMPTYTAG);

            // The DOM Document added starting body and ending tags. We need to remove them.
            $result = preg_replace("/^<body>/", "", $result);
            $result = preg_replace('/<\/body>$/', "", $result);
        } else {
            $result = @$this->dom->saveXML(null, LIBXML_NOEMPTYTAG);
        }
        // saveXML adds closing <br> tags, which breaks formatting.
        $result = preg_replace("/<\/br>/", "", $result);
        return $result;
    }

    /**
     * Get the opening tag of the document.
     *
     * @return string
     */
    private function getDocumentPrefix()
    {
        return <<<HTML
    <html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"></head>
    <body>
HTML;
    }

    /**
     * Get the closing tag of the document.
     * @return string
     */
    private function getDocumentSuffix()
    {
        return "</body></html>";
    }

    /**
     * @inheritDoc
     */
    public function stringify(): FormatText
    {
        $r = new FormatText($this->renderHTML(), WysiwygFormat::FORMAT_KEY);
        return $r;
    }

    /**
     * @inheritDoc
     */
    public function renderHTML(): string
    {
        return $this->getInnerHtml();
    }

    /**
     * @inheritDoc
     */
    public function getFragments(): array
    {
        $fragments = [];
        $this->domToFragments($this->getRoot(), $fragments);
        return $fragments;
    }

    /**
     * Parse a parent node into text fragments.
     *
     * This method recursively iterates over the DOM tree and creates a fragment that consists of only inline format
     * nodes.
     *
     * @param \DOMElement $node The parent node to parse.
     * @param TextFragmentInterface[] $fragments A working array of fragments.
     * @psalm-suppress ConflictingReferenceConstraint I tried to see if this was really a bug, but I can't for the life of me see it.
     */
    private function domToFragments(\DOMElement $node, array &$fragments): void
    {
        $this->attributesToFragments($node, $fragments);
        if (in_array($node->tagName, self::TAG_STOP, true)) {
            return;
        }

        $elementCount = 0;
        $from = $to = null;
        foreach ($node->childNodes as $child) {
            /** @var \DOMNode $child */
            switch ($child->nodeType) {
                case XML_TEXT_NODE:
                    // This is an inline node.
                    if ($from === null) {
                        $from = $child;
                    } else {
                        $to = $child;
                    }
                    $elementCount++;
                    break;
                case XML_ELEMENT_NODE:
                    $elementCount++;
                    /** @var \DOMElement $child */
                    $isInline = in_array($child->tagName, self::TAG_INLINE_TEXT, true);
                    if (!$isInline) {
                        if ($from !== null) {
                            $range = DomUtils::trimRange($from, $to ?? $from);
                            if ($range) {
                                $fragments[] = new HtmlDomRangeFragment(...$range);
                            }
                            $from = $to = null;
                        }
                        $this->domToFragments($child, $fragments);
                    } elseif ($from === null) {
                        $from = $child;
                    } else {
                        $to = $child;
                    }
                    break;
            }
        }
        if ($from !== null && ($range = DomUtils::trimRange($from, $to ?? $from))) {
            $fragments[] = new HtmlDomRangeFragment(...$range);
        }
    }

    /**
     * Make fragments out of a node's attributes.
     *
     * @param \DOMElement $node
     * @param array $fragments
     */
    private function attributesToFragments(\DOMElement $node, array &$fragments)
    {
        foreach ($node->attributes as $key => $attr) {
            /** @var \DOMAttr $attr */
            if (in_array($key, self::FRAGMENT_ATTRIBUTES)) {
                $fragments[] = new HtmlDomAttributeFragment($attr);
            }
        }
    }
}
