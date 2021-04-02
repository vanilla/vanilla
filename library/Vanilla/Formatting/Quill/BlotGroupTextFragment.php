<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\Formatting\TextFragmentInterface;
use Vanilla\Formatting\TextFragmentType;

/**
 * An adaptor for blot groups that allow updating the text in each blot.
 */
final class BlotGroupTextFragment implements TextFragmentInterface {
    /**
     * @var BlotGroup
     */
    private $blotGroup;
    /**
     * @var BlotGroupCollection
     */
    private $parent;

    /**
     * BlotGroupTextFragment constructor.
     *
     * @param BlotGroup $blotGroup
     * @param BlotGroupCollection $parent
     */
    public function __construct(BlotGroup $blotGroup, BlotGroupCollection $parent) {
        $this->blotGroup = $blotGroup;
        $this->parent = $parent;
    }

    /**
     * @inheritDoc
     */
    public function getInnerContent(): string {
        return $this->blotGroup->renderContent();
    }

    /**
     * {@inheritDoc}
     */
    public function setInnerContent(string $text) {
        $blots = HtmlToBlotsParser::parseInlineHtml($text, $this->blotGroup, $this->parent);
        $this->blotGroup->replace($blots);
    }

    /**
     * @inheritDoc
     */
    public function getFragmentType(): string {
        return TextFragmentType::HTML;
    }
}
