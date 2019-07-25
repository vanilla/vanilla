<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;

use Vanilla\Formatting\Quill\BlotGroup;
use Vanilla\Formatting\Quill\Blots\AbstractBlot;
use Vanilla\Formatting\Quill\Blots\TextBlot;

/**
 * Base blot for line terminators. A line terminator should not have any text content. Only newlines and breaks.
 */
abstract class AbstractLineTerminatorBlot extends TextBlot {

    /**
     * Render additional newlines inside of the line.
     *
     * Sometimes the nextOperation which is joined onto a line blot has more than one newline. eg. \n\n\n\n
     * The first one is just to apply the attribute but the additional ones need to be rendered as newlines inside
     * of the group.
     *
     * @see BlotGroup::renderLineGroup()
     *
     * @return string
     */
    public function render(): string {
        $result = "";
        $extraNewLines = substr_count($this->currentOperation["insert"], "\n") - 1;
        for ($i = 0; $i < $extraNewLines; $i++) {
            $result .= $this->renderLineStart()."<br>".$this->renderLineEnd();
        }

        return $result;
    }

    /**
     * If the group already has an overriding blot and it is not the same type as this blot, we start a new group.
     *
     * @param BlotGroup $group The group to check.
     * @return bool
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        $overridingBlot = $group->getOverrideBlot();
        if ($overridingBlot) {
            return get_class($overridingBlot) !== get_class($this);
        } else {
            return parent::shouldClearCurrentGroup($group);
        }
    }

    /**
     * Render the HTML for the start of a line.
     *
     * @see BlotGroup::renderLineGroup()
     *
     * @return string
     */
    abstract public function renderLineStart(): string;

    /**
     * Render the HTML for the end of a line.
     *
     * @see BlotGroup::renderLineGroup()
     *
     * @return string
     */
    abstract public function renderLineEnd(): string;
}
