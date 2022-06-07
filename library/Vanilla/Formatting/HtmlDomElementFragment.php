<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Vanilla\Formatting\Html\DomUtils;

/**
 * A text fragment that points to an element in a single `DOMDocument` node.
 */
class HtmlDomElementFragment implements TextFragmentInterface
{
    /**
     * @var \DOMElement
     */
    private $parent;

    /**
     * @var string
     */
    private $type;

    /**
     * HtmlDomFragment constructor.
     *
     * @param \DOMElement $parent The parent node that owns the content.
     * @param string $type The fragment type, usually HTML.
     */
    public function __construct(\DOMElement $parent, string $type = TextFragmentType::HTML)
    {
        $this->parent = $parent;
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function getInnerContent(): string
    {
        return DomUtils::getInnerHTML($this->parent);
    }

    /**
     * {@inheritDoc}
     */
    public function setInnerContent(string $text)
    {
        DomUtils::setInnerHTML($this->parent, $text);
    }

    /**
     * @inheritDoc
     */
    public function getFragmentType(): string
    {
        return $this->type;
    }

    /**
     * @return \DOMElement
     */
    public function getParent(): \DOMElement
    {
        return $this->parent;
    }

    /**
     * @param \DOMElement $parent
     */
    public function setParent(\DOMElement $parent): void
    {
        $this->parent = $parent;
    }
}
