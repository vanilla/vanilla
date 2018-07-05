<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

abstract class AbstractLineBlot extends TextBlot {
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
    public function renderNewLines(): string {
        $result = "";
        if ($this->nextOperation) {
            $extraNewLines = substr_count($this->nextOperation["insert"], "\n") - 1;
            for ($i = 0; $i < $extraNewLines; $i++) {
                $result .= $this->renderLineStart()."<br>".$this->renderLineEnd();
            }
        }

        return $result;
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
