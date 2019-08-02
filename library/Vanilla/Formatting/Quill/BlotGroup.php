<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\Formatting\Quill\Blots\AbstractBlot;
use Vanilla\Formatting\Quill\Blots\Embeds\ExternalBlot;
use Vanilla\Formatting\Quill\Blots\Lines\AbstractLineTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\Lines\CodeLineTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\Lines\ListLineTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\Lines\ParagraphLineTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\Lines\SpoilerLineTerminatorBlot;
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
        CodeLineTerminatorBlot::class,
        AbstractLineTerminatorBlot::class,
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

    /** @var BlotGroup[] */
    private $nestedGroups = [];

    /**
     * Determine if one group can nest another one inside of it.
     *
     * @param BlotGroup $otherGroup
     * @return bool
     */
    public function canNest(BlotGroup $otherGroup): bool {
        $otherMainBlot = $otherGroup->getMainBlot();
        $ownMainBlot = $this->getMainBlot();
        if ($otherMainBlot instanceof ListLineTerminatorBlot
            && $ownMainBlot instanceof ListLineTerminatorBlot
            && $otherGroup->getNestingDepth() > $this->getNestingDepth()
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return int
     */
    public function getNestingDepth(): int {
        $ownMainBlot = $this->getMainBlot();
        return $ownMainBlot->getNestingDepth();
    }

    /**
     * Nest a blot group inside of this one.
     *
     * @param BlotGroup $blotGroup
     */
    public function nestGroup(BlotGroup $blotGroup) {
        $lastNestedGroup = $this->nestedGroups[count($this->nestedGroups) - 1] ?? null;
        if ($lastNestedGroup && $lastNestedGroup->canNest($blotGroup)) {
            $lastNestedGroup->nestGroup($blotGroup);
        } else {
            $this->nestedGroups[] = $blotGroup;
        }
    }

    /**
     * Render the block.
     *
     * @return string
     */
    public function render(): string {
        // We need a special exception for empty paragraph only blots.
        // This because the ParagraphLineTerminatorBlot does not render a break if there is other group content
        // But does render a break if it is alone. This is mostly to make styling easier.
        // We do not want to need make the paragraph terminator aware of its group.
        if (
            count($this->blots) === 1 &&
            $this->blots[0] instanceof ParagraphLineTerminatorBlot &&
            $this->blots[0]->getContent() === "\n"
        ) {
            return "<p><br></p>";
        }

        // Don't render empty groups.
        $result = '';
        $result .= $this->renderOpeningTag();
        $result .= $this->renderContent();
        $result .= $this->renderClosingTag();
        return $result;
    }

    /**
     * Render the content of a blot group.
     *
     * @return string
     */
    public function renderContent(): string {
        $result = '';
        $surroundTagBlot = $this->getMainBlot();

        if ($surroundTagBlot instanceof AbstractLineTerminatorBlot) {
            // Line blots have special rendering.
            $result .= $this->renderLineGroup();
        } else {
            foreach ($this->blots as $blot) {
                $result .= $blot->render();
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function renderOpeningTag(): string {
        $surroundTagBlot = $this->getMainBlot();
        return $surroundTagBlot->getGroupOpeningTag();
    }

    /**
     * @return string
     */
    public function renderClosingTag(): string {
        $surroundTagBlot = $this->getMainBlot();
        return $surroundTagBlot->getGroupClosingTag();
    }

    /**
     * Render any nested groups inside of this one.
     *
     * If there are multiple nested groups they will share the start/end tag of the first nested group.
     *
     * @return string
     */
    private function renderNestedGroups(): string {
        $firstNestedGroup = $this->nestedGroups[0] ?? null;
        if (!$firstNestedGroup) {
            return "";
        }

        $result = "";
        $result .= $firstNestedGroup->renderOpeningTag();

        foreach ($this->nestedGroups as $nestedGroup) {
            // Only the first group will be used for the group tags.
            $result .= $nestedGroup->renderContent();
        }
        $result .= $firstNestedGroup->renderClosingTag();
        return $result;
    }

    /**
     * Render out a group with line terminators.
     *
     * We need to render potentially multiple inline blots than a line terminator, but the line terminator also
     * is responsible for the opening tag of the line and each line can have its special attributes even if
     * they are the same blot (eg. list indentation).
     *
     * @return string
     */
    public function renderLineGroup(): string {
        $result = "";

        // Look ahead and get all of the line terminators.
        $terminatorIndex = 0;
        $terminators = $this->getLineTerminators();
        $terminator = $terminators[$terminatorIndex];

        // Grab the first line terminator and start the line.
        $result .= $terminator->renderLineStart();

        foreach ($this->blots as $index => $blot) {
            $isLast = $index === count($this->blots) - 1;
            if ($blot instanceof AbstractLineTerminatorBlot) {
                // Render out the content of the line terminator (maybe nothing, maybe extra newlines).
                $result .= $terminator->render();
                if ($isLast) {
                    // render the nested groups inside of the last line, before it's closing tag. Eg.
                    // <li>Line 1 <ul>
                    //     <li>Line 1.1</li>
                    // </ul></li>
                    $result .= $this->renderNestedGroups();
                }
                // End the line.
                $result .= $terminator->renderLineEnd();

                // Start the next line.
                $terminatorIndex++;
                $terminator = $terminators[$terminatorIndex] ?? null;
                if ($terminator !== null) {
                    $result .= $terminator->renderLineStart();
                }
            } else {
                // Render out inline blots.
                $result .= $blot->render();
            }
        }

        return $result;
    }

    /**
     * Get all of the line terminators in the group.
     *
     * @return array
     */
    private function getLineTerminators(): array {
        $terminators = [];
        foreach ($this->blots as $blot) {
            if ($blot instanceof AbstractLineTerminatorBlot) {
                $terminators[] = $blot;
            }
        }
        return $terminators;
    }

    /**
     * Get all of the usernames that are mentioned in the blot group.
     *
     * Mentions that are inside of Blockquote's are excluded. We don't want to be sending notifications when big quote
     * replies build up.
     *
     * @return string[]
     */
    public function getMentionUsernames() {
        if ($this->getMainBlot() instanceof Blots\Lines\BlockquoteLineTerminatorBlot) {
            return [];
        }

        $names = [];
        foreach ($this->blots as $blot) {
            if ($blot instanceof Blots\Embeds\MentionBlot) {
                $names[] = $blot->getUsername();
            } elseif ($blot instanceof ExternalBlot) {
                $embed = $blot->getEmbed();
                if ($embed instanceof QuoteEmbed) {
                    $names[] = $embed->getUsername();
                }
            }
        }

        // De-duplicate the usernames.
        $names = array_unique($names);

        return $names;
    }

    /**
     * Add a blot to this block.
     *
     * @param AbstractBlot[] $blots
     */
    public function pushBlots(array $blots) {
        foreach ($blots as $blot) {
            $this->blots[] = $blot;
        }
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
    public function getMainBlot(): ?AbstractBlot {
        if (count($this->blots) === 0) {
            return null;
        }
        $blot = $this->blots[0];
        $overridingBlot = $this->getOverrideBlot();

        return $overridingBlot ?? $blot;
    }

    /**
     * Get the primary blot for the group.
     *
     * @return null|AbstractBlot
     */
    public function getOverrideBlot(): ?AbstractBlot {
        foreach ($this->overridingBlots as $overridingBlot) {
            $index = $this->getIndexForBlotOfType($overridingBlot);
            if ($index >= 0) {
                return $this->blots[$index];
            }
        }

        return null;
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

    /**
     * Get the text value of a heading. This is not sanitized, so be sure to HTML escape before using.
     *
     * @return string
     */
    public function getUnsafeText(): string {
        $text = "";
        $mainBlot = $this->getMainBlot();
        if ($mainBlot instanceof SpoilerLineTerminatorBlot) {
            return \Gdn::translate("(Spoiler)\n");
        }

        foreach ($this->blots as $blot) {
            $text .= $blot->getContent();
        }

        return $text;
    }
}
