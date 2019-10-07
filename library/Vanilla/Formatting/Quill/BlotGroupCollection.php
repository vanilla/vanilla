<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\Formatting\Quill\Blots\AbstractBlot;
use Vanilla\Formatting\Quill\Blots\Lines\AbstractLineTerminatorBlot;

/**
 * Class for sorting operations into blots and groups.
 */
class BlotGroupCollection implements \IteratorAggregate {

    /** @var array[] The operations to parse. */
    private $operations;

    /** @var string[] The blots that we are allowed to parse out. */
    private $allowedBlotClasses;

    /** @var string The parsing mode to be passed through to blots when they are created. */
    private $parseMode = Parser::PARSE_MODE_NORMAL;

    /** @var BlotGroup[] The array of groups being built up. */
    private $groups;

    /** @var AbstractBlot The blot being build up in the iteration */
    private $inProgressBlot;

    /** @var BlotGroup the group being built up in the iteration. */
    private $inProgressGroup;

    /** @var array[] the line being built up in the iteration. */
    private $inProgressLine;

    /** @var array The primary operation being iterated. */
    private $currentOp;

    /** @var array The next operation being iterated. */
    private $nextOp;

    /** @var array The previous operation being iterated. */
    private $prevOp;

    // ITERABLE IMPLEMENTATION

    /**
     * @inheritdoc
     */
    public function getIterator() {
        return new \ArrayIterator($this->groups);
    }

    /**
     * BlotGroupCollection constructor.
     *
     * @param array[] $operations The operations to generate groups for.
     * @param string[] $allowedBlotClasses The class names of the blots we are allowed to create in the groups.
     * @param string $parseMode The parsing mode to create the blots with.
     */
    public function __construct(array $operations, array $allowedBlotClasses, string $parseMode) {
        $this->operations = $operations;
        $this->allowedBlotClasses = $allowedBlotClasses;
        $this->parseMode = $parseMode;
        $this->createBlotGroups();
    }

    /**
     * Push the current line into the group and reset the line.
     */
    private function clearLine() {
        $this->inProgressGroup->pushBlots($this->inProgressLine);
        $this->inProgressLine = [];
    }

    /**
     * Get the previous blot group.
     *
     * @return BlotGroup|null
     */
    private function getPreviousBlotGroup(): ?BlotGroup {
        return $this->groups[count($this->groups) - 1] ?? null;
    }

    /**
     * Push the group into our groups array and start a new one.
     * Do not push an empty group.
     */
    private function clearBlotGroup() {
        if ($this->inProgressGroup->isEmpty()) {
            return;
        }

        $currentGroup = $this->inProgressGroup;
        $prevGroup = $this->getPreviousBlotGroup();

        if ($prevGroup && $prevGroup->canNest($currentGroup)) {
            $prevGroup->nestGroup($currentGroup);
        } elseif ($prevGroup && $prevGroup->canMerge($currentGroup)) {
            // Merge the current group into the previous one.
            $prevGroup->pushBlots($currentGroup->getBlotsAndGroups());
        } else {
            $this->groups[] = $this->inProgressGroup;
        }

        $this->inProgressGroup = new BlotGroup();
    }

    /**
     * Create Blots and their groups.
     */
    private function createBlotGroups() {
        $this->inProgressGroup = new BlotGroup();
        $this->inProgressLine = [];
        $this->groups = [];

        for ($i = 0; $i < count($this->operations); $i++) {
            $this->currentOp = $this->operations[$i];
            $this->prevOp = $this->operations[$i - 1] ?? [];
            $this->nextOp = $this->operations[$i + 1] ?? [];
            $this->inProgressBlot = $this->getCurrentBlot();

            // In event of break blots we want to clear the group if applicable and the skip to the next item.
            if ($this->currentOp === Parser::BREAK_OPERATION) {
                $this->clearLine();
                $this->clearBlotGroup();
                continue;
            }

            if (($this->inProgressBlot->shouldClearCurrentGroup($this->inProgressGroup))) {
                // Ask the blot if it should close the current group.
                $this->clearBlotGroup();
            }

            // Ask the blot if it should close the current group.
            if ($this->inProgressBlot instanceof AbstractLineTerminatorBlot) {
                // Clear the line because we we had line terminator.
                $this->clearLine();
            }

            // Push the blot into our current line.
            $this->inProgressLine[] = $this->inProgressBlot;

            // Clear the line because we have a line terminator.
            if ($this->inProgressBlot instanceof AbstractLineTerminatorBlot) {
                $this->clearLine();
            }

            // Some block type blots get a group (and line) all to themselves.
            if ($this->inProgressBlot->isOwnGroup()) {
                $this->clearLine();
                $this->clearBlotGroup();
            }
        }

        // Iteration is done so we need to clear the line then the group.
        $this->clearLine();
        if (!$this->inProgressGroup->isEmpty()) {
            $this->clearBlotGroup();
        }
    }

    /**
     * Get the matching blot for a sequence of operations. Returns the default if no match is found.

     * @return AbstractBlot
     */
    public function getCurrentBlot(): AbstractBlot {
        // Fallback to a TextBlot if possible. Otherwise we fallback to rendering nothing at all.
        $blotClass = Blots\TextBlot::matches($this->currentOp) ? Blots\TextBlot::class : Blots\NullBlot::class;
        foreach ($this->allowedBlotClasses as $blot) {
            // Find the matching blot type for the current, last, and next operation.
            if ($blot::matches($this->currentOp)) {
                $blotClass = $blot;
                break;
            }
        }

        return new $blotClass($this->currentOp, $this->prevOp, $this->nextOp, $this->parseMode);
    }
}
