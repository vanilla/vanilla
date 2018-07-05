<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill;

use Vanilla\Quill\Blots;
use Vanilla\Quill\Blots\AbstractBlot;
use Vanilla\Quill\Formats\AbstractFormat;

/**
 * Class for parsing Quill Deltas into BlotGroups.
 *
 * @see https://github.com/quilljs/delta Information on quill deltas.
 */
class Parser {

    const DEFAULT_BLOT = Blots\NullBlot::class;

    /** @var string[] The registered blot classes */
    private $blotClasses = [];

    /** @var string[] The registered formats classes */
    private $formatClasses = [];

    /**
     * Add a new embed type.
     *
     * @param string $blotClass The blot to register.
     *
     * @see AbstractBlot
     * @return $this
     */
    public function addBlot(string $blotClass) {
        $this->blotClasses[] = $blotClass;

        return $this;
    }

    /**
     * Register all of the built in blots and formats to parse. Primarily for use in bootstrapping.
     */
    public function addCoreBlotsAndFormats() {
        $this->addBlot(Blots\Embeds\ExternalBlot::class)
            ->addBlot(Blots\CodeBlockBlot::class)
            ->addBlot(Blots\SpoilerLineBlot::class)
            ->addBlot(Blots\BlockquoteLineBlot::class)
            ->addBlot(Blots\HeadingBlot::class)
            ->addBlot(Blots\BulletedListBlot::class)
            ->addBlot(Blots\OrderedListBlot::class)
            ->addBlot(Blots\TextBlot::class)
            ->addBlot(Blots\Embeds\MentionBlot::class)
            ->addBlot(Blots\Embeds\EmojiBlot::class)
            ->addFormat(Formats\Link::class)
            ->addFormat(Formats\Bold::class)
            ->addFormat(Formats\Italic::class)
            ->addFormat(Formats\Code::class)
            ->addFormat(Formats\Strike::class)
        ;
    }

    /**
     * Add a new embed type.
     *
     * @param string $formatClass The Format class to register.
     *
     * @see AbstractFormat
     * @return $this
     */
    public function addFormat(string $formatClass) {
        $this->formatClasses[] = $formatClass;

        return $this;
    }

    /**
     * Split operations with newlines inside of them into their own operations.
     */
    public function splitPlainTextNewlines($operations) {
        $newOperations = [];

        foreach ($operations as $opIndex => $op) {
            // Determine if this is a plain text insert with no attributes.
            $isBareInsertOperation =
                !array_key_exists("attributes", $op)
                && array_key_exists("insert", $op)
                && is_string($op["insert"]);

            // Other types of inserts should not get split, they need special handling inside of their blot.
            // Just skip over them.
            if (!$isBareInsertOperation) {
                $newOperations[] = $op;
                continue;
            }

            // A newline on its own needs special handling. We don't want to break it into 2 newlines.
            if ($op["insert"] === "\n") {
                $op[BlotGroup::BREAK_MARKER] = true;
                $op["insert"] = "";
                $newOperations[] = $op;
                continue;
            }

            // Explode on newlines into new operations. Every new operation,
            // and the next old operation should get a BREAK_MARKER.
            $pieces = \explode("\n", $op["insert"]);
            if (count($pieces) > 1) {
                // Create a new insert from the exploded piece.
                foreach ($pieces as $index => $piece) {
                    $insert = ["insert" => $piece];
                    $insert[BlotGroup::BREAK_MARKER] = true;
                    $newOperations[] = $insert;
                }

                $isNotLastOperation = $opIndex < count($operations) - 1;
                $isNewLineOnly = $newOperations[count($newOperations) - 1]["insert"] === "";

                // Set a marker on the next blot if the last piece is a newline.
                if ($isNotLastOperation && $isNewLineOnly) {
                    $operations[$opIndex + 1][BlotGroup::BREAK_MARKER] = true;
                }
            } else {
                $newOperations[] = $op;
            }
        }

        return $newOperations;
    }

    /**
     * Parse the operations into an array of Groups.
     *
     * @return BlotGroup[]
     */
    public function parse(array $operations): array {

        $operations = $this->splitPlainTextNewlines($operations);

        $groups = [];
        $operationLength = count($operations);
        $group = new BlotGroup();

        for ($i = 0; $i < $operationLength; $i++) {
            $previousOp = $operations[$i - 1] ?? [];
            $currentOp = $operations[$i];
            $nextOp = $operations[$i + 1] ?? [];
            $isFirst = $i === 0;
            $isLast = $i === $operationLength - 1;

            // Skip the last newline (unless its the only one).
            if (!$isFirst && $isLast && array_key_exists(BlotGroup::BREAK_MARKER, $currentOp)) {
                continue;
            }

            $blotInstance = $this->getBlotForOperations($currentOp, $previousOp, $nextOp);

            // Ask the blot if it should close the current group.
            if ($blotInstance->shouldClearCurrentGroup($group) && !$group->isEmpty()) {
                $groups[] = $group;
                $group = new BlotGroup();
            }

            $group->pushBlot($blotInstance);

            // Some block type blots get a group all to themselves.
            if ($blotInstance instanceof Blots\AbstractBlockBlot && $blotInstance->isOwnGroup()  && !$group->isEmpty()) {
                $groups[] = $group;
                $group = new BlotGroup();
            }

            // Check with the blot if absorbed the next operation. If it did we don't want to iterate over it.
            if ($blotInstance->hasConsumedNextOp()) {
                $i++;
            }
        }
        if (!$group->isEmpty()) {
            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * Get the matching blot for a sequence of operations. Returns the default if no match is found.
     */
    public function getBlotForOperations($currentOp, $previousOp, $nextOp): AbstractBlot {
        $blotClass = self::DEFAULT_BLOT;
        foreach ($this->blotClasses as $blot) {
            // Find the matching blot type for the current, last, and next operation.
            if ($blot::matches([$currentOp, $nextOp])) {
                $blotClass = $blot;
                break;
            }
        }

        return new $blotClass($currentOp, $previousOp, $nextOp);
    }

    /**
     * Get the matching format for a sequence of operations if applicable.
     *
     * @returns AbstractFormat[] The formats matching the given operations.
     */
    public function getFormatsForOperations($currentOp, $previousOp, $nextOp): array {
        $formats = [];
        foreach ($this->formatClasses as $format) {
            if ($format::matches([$currentOp])) {
                /** @var Formats\AbstractFormat $formatInstance */
                $formats[] = new $format($currentOp, $previousOp, $nextOp);
            }
        }

        return $formats;
    }

    /**
     * Call parse and then simplify each blotGroup into test data.
     *
     * @param array $ops The ops to parse
     */
    public function parseIntoTestData(array $ops): array {
        $parseData = $this->parse($ops);
        $groupData = [];
        foreach ($parseData as $blotGroup) {
            $groupData[] = $blotGroup->getTestData();
        }

        return $groupData;
    }
}
