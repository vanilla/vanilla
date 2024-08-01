<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

/**
 * A text fragment that points to a specific HTML attribute like a title or alt.
 */
class HtmlDomAttributeFragment implements TextFragmentInterface
{
    /**
     * @var \DOMAttr
     */
    private $attr;

    /**
     * HtmlDomAttributeFragment constructor.
     *
     * @param \DOMAttr $attr
     */
    public function __construct(\DOMAttr $attr)
    {
        $this->attr = $attr;
    }

    /**
     * {@inheritDoc}
     */
    public function getInnerContent(): string
    {
        return $this->attr->nodeValue;
    }

    /**
     * {@inheritDoc}
     */
    public function setInnerContent(string $text)
    {
        $this->attr->nodeValue = $text;
    }

    /**
     * {@inheritDoc}
     */
    public function getFragmentType(): string
    {
        return TextFragmentType::TEXT;
    }

    /**
     * Get the attribute that the fragment represents.
     *
     * @return \DOMAttr
     */
    public function getAttr(): \DOMAttr
    {
        return $this->attr;
    }

    /**
     * Set the attribute that the fragment represents.
     *
     * @param \DOMAttr $attr
     */
    public function setAttr(\DOMAttr $attr): void
    {
        $this->attr = $attr;
    }
}
