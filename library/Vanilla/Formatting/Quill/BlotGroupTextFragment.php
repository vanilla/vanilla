<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\Formatting\Quill\Blots\Lines\CodeLineTerminatorBlot;
use Vanilla\Formatting\TextFragmentInterface;
use Vanilla\Formatting\TextFragmentType;

/**
 * An adaptor for blot groups that allow updating the text in each blot.
 */
final class BlotGroupTextFragment implements TextFragmentInterface
{
    /**
     * @var BlotGroup
     */
    private $blotGroup;
    /**
     * @var BlotGroupCollection
     */
    private $parent;

    /**
     * @var object
     */
    private $from;

    /**
     * @var object
     */
    private $to;

    /**
     * BlotGroupTextFragment constructor.
     *
     * @param BlotGroup $blotGroup
     * @param BlotGroupCollection $parent
     * @param int $from The starting ordinal in the group that the text represents.
     * @param ?int $to The ending ordinal in the group that the text represents.
     */
    public function __construct(BlotGroup $blotGroup, BlotGroupCollection $parent, int $from = 0, int $to = null)
    {
        $this->blotGroup = $blotGroup;
        $this->parent = $parent;

        $blots = $blotGroup->getBlotsAndGroups();
        if ($to === null) {
            $to = count($blots) - 1;
        }
        $this->from = $blots[$from];
        $this->to = $blots[$to];

        $this->fromIndex = $from;
        $this->toIndex = $to;
    }

    /**
     * @inheritDoc
     */
    public function getInnerContent(): string
    {
        $r = $this->blotGroup->renderPartialLineGroupContent($this->getFromIndex());
        return $r;
    }

    /**
     * {@inheritDoc}
     */
    public function setInnerContent(string $text)
    {
        $blots = HtmlToBlotsParser::parseInlineHtml($text, $this->parent);

        $from = $this->getFromIndex();
        $to = $this->getToIndex();
        $this->blotGroup->replace($blots, $from, $to - 1);
    }

    /**
     * @inheritDoc
     */
    public function getFragmentType(): string
    {
        if ($this->to instanceof CodeLineTerminatorBlot) {
            return TextFragmentType::CODE;
        }
        return TextFragmentType::HTML;
    }

    /**
     * @return false|int|string
     */
    private function getFromIndex()
    {
        return array_search($this->from, $this->blotGroup->getBlotsAndGroups(), true);
    }

    /**
     * @return false|int|string
     */
    private function getToIndex()
    {
        return array_search($this->to, $this->blotGroup->getBlotsAndGroups(), true);
    }
}
