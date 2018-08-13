<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\Formatting\Quill\Blots\AbstractBlot;
use Vanilla\Formatting\Quill\Blots\Lines\AbstractLineBlot;

class BlotGroupFactory {

    /** @var array[] */
    private $operations;
    /** @var string[]  */
    private $allowedBlotClasses;
    /** @var string */
    private $parseMode = Parser::PARSE_MODE_NORMAL;
    /** @var AbstractBlot */
    private $inProgressBlot;
    /** @var BlotGroup */
    private $inProgressGroup;
    /** @var array[] */
    private $inProgressLine;
    /** @var BlotGroup[] */
    private $groups;

    private $currentOp;
    private $nextOp;
    private $prevOp;

    /**
     * @return BlotGroup[]
     */
    public function getGroups(): array {
        return $this->groups;
    }

    /**
     * BlotGroupFactory constructor.
     *
     * @param array[] $operations
     * @param string[] $allowedBlotClasses
     */
    public function __construct(array $operations, array $allowedBlotClasses) {
        $this->operations = $operations;
        $this->allowedBlotClasses = $allowedBlotClasses;
        $this->inProgressGroup = new BlotGroup();
        $this->inProgressLine = [];
        $this->groups = [];
        $this->createBlotGroups();
    }

    private function clearLine() {
        $this->inProgressGroup->pushBlots($this->inProgressLine);
        $this->inProgressLine = [];
    }

    private function clearBlotGroup() {
        if (!$this->inProgressGroup->isEmpty()) {
            $this->groups[] = $this->inProgressGroup;
            $this->inProgressGroup = new BlotGroup();
        }
    }


    /**
     * Create blot groups out of an array of operations.
     *
     * @param array $operations The pre-parsed operations
     * @param string $parseMode The parse mode to create blots with.
     *
     * @return BlotGroup[]
     */
    private function createBlotGroups() {
        $operationLength = count($this->operations);

        for ($i = 0; $i < $operationLength; $i++) {
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

            // Ask the blot if it should close the current group.
            if ($this->inProgressBlot instanceof AbstractLineBlot) {
                if (($this->inProgressBlot->shouldClearCurrentGroup($this->inProgressGroup))) {
                    $this->clearBlotGroup();
                }
                $this->clearLine();
            }

            $this->inProgressLine[] = $this->inProgressBlot;

            if ($this->inProgressBlot instanceof AbstractLineBlot) {
                $this->clearLine();
            }

            // Some block type blots get a group all to themselves.
            if ( $this->inProgressBlot->isOwnGroup()) {
                $this->clearLine();
                $this->clearBlotGroup();
            }
        }
        $this->clearLine();
        if (!$this->inProgressGroup->isEmpty()) {
            $this->groups[] = $this->inProgressGroup;
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
