<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill;

use Vanilla\Quill\Blots\AbstractBlot;
use Vanilla\Quill\Blots\AbstractLineBlot;
use Vanilla\Quill\Blots\AbstractListBlot;
use Vanilla\Quill\Blots\CodeBlockBlot;
use Vanilla\Quill\Blots\HeadingBlot;
use Vanilla\Quill\Blots\TextBlot;

/**
 * Class to represent a group of a quill blots. One Group can contain multiple inline type blots.
 *
 * @package Vanilla\Quill
 */
class BlotGroup {

    const BREAK_MARKER = "group-break-marker";

    /** @var AbstractBlot[] */
    private $blots = [];

    /**
     * @var AbstractBlot[]
     *
     * Blots that can determine the surrounding tag over the other blot types.
     */
    private $overridingBlots = [
        HeadingBlot::class,
        CodeBlockBlot::class,
        AbstractListBlot::class,
        AbstractLineBlot::class,
    ];

    /**
     * Create any empty group. When rendered it will output <p><br></p>
     *
     * @return BlotGroup
     */
    public static function makeEmptyGroup(): BlotGroup {
        $group = new BlotGroup();
        $blot = new TextBlot(["insert" => ""], [], []);
        $blot->setContent("<br>");
        $group->pushBlot($blot);
        return $group;
    }

    /**
     * Determine if this group is made up only of a break.
     *
     * @return bool
     */
    public function isBreakOnlyGroup(): bool {
        if (count($this->blots) !== 1) {
            return false;
        }

        $blot = $this->blots[0];
        return get_class($blot) === TextBlot::class && $this->blots[0]->getContent() === "";
    }

    /**
     * Check if the group's last matches the passed Blot class.
     *
     * @param $blotClass - The class constructor of a Blot.
     * @param bool $needsExactMatch - If true, the class must match exactly, no child classes are allowed.
     *
     * @return bool
     */
    public function endsWithBlotOfType(string $blotClass, bool $needsExactMatch = false): bool {
        if (count($this->blots) === 0) {
            return false;
        }

        $lastBlot = $this->blots[count($this->blots) - 1];

        if ($needsExactMatch) {
            return get_class($lastBlot) === $blotClass;
        }

        return $lastBlot instanceof $blotClass;
    }

    /**
     * Render the block.
     *
     * @return string
     */
    public function render(): string {
        // Don't render empty groups.
        if (count($this->blots) === 0) {
            return "";
        }

        $surroundTagBlot = $this->getBlotForSurroundingTags();
        $result = $surroundTagBlot->getGroupOpeningTag();

        // Line blots have special rendering.
        if ($surroundTagBlot instanceof AbstractLineBlot) {
            $result .= $this->renderLineGroup($surroundTagBlot);
        } else {
            foreach ($this->blots as $blot) {
                // Don't render breaks unless the group is just a break.
                $blotIsBreak = get_class($blot) === TextBlot::class && $blot->getContent() === "";
                if (!$this->isBreakOnlyGroup() && $blotIsBreak) {
                    continue;
                }

                $result .= $blot->render();
            }
        }

        $result .= $surroundTagBlot->getGroupClosingTag();
        return $result;
    }

    /**
     * Render out a line group. These need special handling just like they do on the frontend.
     *
     * Formats (or anything needed to be rendered the the TextBlots render) like bold and italic are not LineBlots even
     * if they need to be contained in the line, so we determined what type of line wrapper we need, render the start
     * of the line, and don't render the close until we get to another line blot.
     *
     * @param AbstractLineBlot $firstLineBlot - The first line blot found in the group. This is not necessarily the
     * first blot in the group.
     *
     * @return string
     */
    private function renderLineGroup(AbstractLineBlot $firstLineBlot): string {
        $result = "";

        $result .= $firstLineBlot->renderLineStart();

        foreach ($this->blots as $index => $blot) {
            // Ignore starting newlines.
            if ($index === 0 && $blot->getContent() === "") {
                continue;
            }
            $result .= $blot->render();

            if ($blot instanceof AbstractLineBlot) {
                $result .= $blot->renderLineEnd();
                $result .= $blot->renderNewLines();

                if ($index < count($this->blots) - 1) {
                    $result .= $blot->renderLineStart();
                }
            }
        }

        return $result;
    }

    /**
     * Add a blot to this block.
     *
     * @param AbstractBlot $blot
     */
    public function pushBlot(AbstractBlot $blot) {
        $this->blots[] = $blot;
    }

    /**
     * Get the position in the blots array of the first blot of the given type. Defaults to -1.
     *
     * @param string $blotType
     *
     * @return int
     */
    public function getIndexForBlotOfType(string $blotType): int {
        $index = -1;

        foreach($this->blots as $blotIndex => $blot) {
            if($blot instanceof $blotType) {
                $index = $blotIndex;
                break;
            }
        }

        return $index;
    }

    /**
     * Determine which blot should create the surrounding HTML tags of the group.
     *
     * @return AbstractBlot
     */
    public function getBlotForSurroundingTags(): AbstractBlot {
        $blot = $this->blots[0];

        foreach($this->overridingBlots as $overridingBlot) {
            $index = $this->getIndexForBlotOfType($overridingBlot);
            if ($index >= 0) {
                $blot = $this->blots[$index];
                break;
            }
        }

        return $blot;
    }
}
