<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\Formatting\Quill\Blots\AbstractBlot;
use Vanilla\Formatting\Quill\Blots\Lines\AbstractLineBlot;
use Vanilla\Formatting\Quill\Blots\CodeBlockBlot;
use Vanilla\Formatting\Quill\Blots\HeadingBlot;
use Vanilla\Formatting\Quill\Blots\TextBlot;

/**
 * Class to represent a group of a quill blots. One group can contain:
 *
 * - Multiple Text formats in a single paragraph (as well as inline embeds).
 * - Multiple line blots if it is a line type grouping
 */
class BlotGroup {
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
        AbstractLineBlot::class,
    ];

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
     * @param string $blotClass - The class constructor of a Blot.
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
     * Determine if the group is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool {
        return count($this->blots) === 0;
    }

    /**
     * Render the block.
     *
     * @return string
     */
    public function render(): string {
        // Don't render empty groups.
        $surroundTagBlot = $this->getBlotForSurroundingTags();
        $result = $surroundTagBlot->getGroupOpeningTag();

        // Line blots have special rendering.
        if ($surroundTagBlot instanceof AbstractLineBlot) {
            $result .= $this->renderLineGroup($surroundTagBlot);
        } else {
            foreach ($this->blots as $blot) {
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
    public function renderLineGroup(AbstractLineBlot $firstLineBlot): string {
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
                    // TODO: This is kind of fragile and needs tests.
                    $nextLine = $this->blots[$index + 1] ?? null;
                    if ($nextLine instanceof AbstractLineBlot) {
                        $result .= $nextLine->renderLineStart();
                    } else {
                        $result .= $blot->renderLineStart();
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get all of the mention blots in the group.
     *
     * Mentions that are inside of Blockquote's are excluded. We don't want to be sending notifications when big quote
     * replies build up.
     *
     * @return string[]
     */
    public function getMentionUsernames() {
        if ($this->getBlotForSurroundingTags() instanceof Blots\Lines\BlockquoteLineBlot) {
            return [];
        }

        $names = [];
        foreach ($this->blots as $blot) {
            if ($blot instanceof Blots\Embeds\MentionBlot) {
                $names[] = $blot->getUsername();
            }
        }

        return $names;
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
     * @param string $blotClass The class string of the blot to check for.
     *
     * @return int
     */
    public function getIndexForBlotOfType(string $blotClass): int {
        $index = -1;

        foreach ($this->blots as $blotIndex => $blot) {
            if ($blot instanceof $blotClass) {
                $index = $blotIndex;
                break;
            }
        }

        return $index;
    }

    /**
     * Determine which blot should create the surrounding HTML tags of the group.
     *
     * @return AbstractBlot|null
     */
    public function getBlotForSurroundingTags() {
        if (count($this->blots) === 0) {
            return null;
        }
        $blot = $this->blots[0];

        foreach ($this->overridingBlots as $overridingBlot) {
            $index = $this->getIndexForBlotOfType($overridingBlot);
            if ($index >= 0) {
                $blot = $this->blots[$index];
                break;
            }
        }

        return $blot;
    }

    /**
     * Simplify the blot group into an array of the classnames of its blots.
     *
     * @return string[]
     */
    public function getTestData(): array {
        $blots = [];
        foreach ($this->blots as $blot) {
            $blots[] = [
                "class" => get_class($blot),
                "content" => $blot->getContent(),
            ];
        }
        return $blots;
    }
}
