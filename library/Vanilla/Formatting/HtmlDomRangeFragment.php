<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Vanilla\Formatting\Html\DomUtils;

/**
 * A text fragment that points to a range of HTML elements.
 */
class HtmlDomRangeFragment implements TextFragmentInterface
{
    /**
     * @var \DOMNode
     */
    private $from;

    /**
     * @var \DOMNode
     */
    private $to;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $suffix;

    /**
     * @var string
     */
    private $type;

    /**
     * HtmlDomRangeFragment constructor.
     *
     * @param \DOMNode $from The node the range starts from.
     * @param \DOMNode $to The node the range goes to.
     * @param string $type The type of fragment. One of the `TextFragmentType` constants.
     */
    public function __construct(\DOMNode $from, \DOMNode $to, string $type = TextFragmentType::HTML)
    {
        if ($from->parentNode !== $to->parentNode) {
            throw new \InvalidArgumentException('DomUtils::setHTMLRange() expects $from and $to to be siblings.', 400);
        }

        $this->from = $from;
        $this->to = $to;
        $this->setPrefixSuffix();
        $this->type = $type;
    }

    /**
     * Get the node the range starts from.
     *
     * @return \DOMNode
     */
    public function getFrom(): \DOMNode
    {
        return $this->from;
    }

    /**
     * Get the node the range goes to.
     *
     * @return \DOMNode
     */
    public function getTo(): \DOMNode
    {
        return $this->to;
    }

    /**
     * @inheritDoc
     */
    public function getInnerContent(): string
    {
        return trim(DomUtils::getHtmlRange($this->from, $this->to));
    }

    /**
     * {@inheritDoc}
     */
    public function setInnerContent(string $text)
    {
        [$this->from, $this->to] = DomUtils::setHtmlRange(
            $this->from,
            $this->to,
            $this->prefix . $text . $this->suffix
        );
        $this->setPrefixSuffix();
    }

    /**
     * {@inheritDoc}
     */
    public function getFragmentType(): string
    {
        return $this->type;
    }

    /**
     * Set the prefix/suffix string from the current from/to in the range.
     */
    private function setPrefixSuffix(): void
    {
        if ($this->from instanceof \DOMText && preg_match("`^(\s+)`", $this->from->nodeValue, $m)) {
            $this->prefix = $m[1];
        }
        if ($this->to instanceof \DOMText && preg_match('`(\s+)$`', $this->to->nodeValue, $m)) {
            $this->suffix = $m[1];
        }
    }
}
