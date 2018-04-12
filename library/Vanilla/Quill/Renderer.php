<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill;

use Vanilla\Quill\Blots;
use Vanilla\Quill\Blots\AbstractBlot;

/**
 * Class for rendering quill deltas into HTML.
 *
 * @see https://github.com/quilljs/delta Information on quill deltas.
 *
 * @package Vanilla\Quill
 */
class Renderer {

    /**
     * The blot types to check for. Not all blot types are used at the top level.
     */
    const BLOTS = [
        Blots\CodeBlockBlot::class,
        Blots\SpoilerLineBlot::class,
        Blots\BlockquoteLineBlot::class,
        Blots\HeadingBlot::class,
        Blots\BulletedListBlot::class,
        Blots\OrderedListBlot::class,
        Blots\Embeds\EmojiBlot::class,
        Blots\Embeds\ImageBlot::class,
        Blots\Embeds\VideoBlot::class,
        Blots\Embeds\LinkEmbedBlot::class,
        Blots\TextBlot::class,
    ];

    /** @var array[] */
    private $operations = [];

    /** @var BlotGroup[]  */
    private $groups = [];

    /**
     * Parser constructor.
     *
     * @param array $operations
     */
    public function __construct(array $operations) {
        $this->operations = $operations;
        $this->splitPlainTextNewlines();
        $this->parse();
    }

    /**
     * Split operations with newlines inside of them into their own operations.
     */
    private function splitPlainTextNewlines() {
        $newOperations = [];

        foreach($this->operations as $opIndex => $op) {
            // Determine if this is a plain text insert with no attributes.
            $isBareInsertOperation =
                !\array_key_exists("attributes", $op)
                && \array_key_exists("insert", $op)
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
                foreach($pieces as $index => $piece) {
                    $insert = ["insert" =>  $piece];
                    $insert[BlotGroup::BREAK_MARKER] = true;
                    $newOperations[] = $insert;
                }

                $isNotLastOperation = $opIndex < count($this->operations) - 1;
                $isNewLineOnly = $newOperations[count($newOperations) - 1]["insert"] === "";

                // Set a marker on the next blot if the last piece is a newline.
                if ($isNotLastOperation && $isNewLineOnly) {
                    $this->operations[$opIndex + 1][BlotGroup::BREAK_MARKER] = true;
                }
            } else {
                $newOperations[] = $op;
            }
        }

        $this->operations = $newOperations;
    }

    /**
     * Parse the operations into an array of Groups.
     */
    private function parse() {
        $operationLength = \count($this->operations);
        $group = new BlotGroup();

        for($i = 0; $i < $operationLength; $i++) {

            $previousOp = [];
            $currentOp = $this->operations[$i];
            $nextOp = [];

            if ($i > 0) {
                $previousOp = $this->operations[$i - 1];
            }

            if ($i < $operationLength - 1) {
                $nextOp = $this->operations[$i + 1];
            }

            foreach(self::BLOTS as $blot) {

                // Find the matching blot type for the current, last, and next operation.
                if ($blot::matches([$currentOp, $nextOp])) {
                    /** @var AbstractBlot $blotInstance */
                    $blotInstance = new $blot($currentOp, $previousOp, $nextOp);

                    // Ask the blot if it should close the current group.
                    if ($blotInstance->shouldClearCurrentGroup($group)) {
                        $this->groups[] = $group;
                        $group = new BlotGroup();
                    }

                    $group->pushBlot($blotInstance);

                    // Check with the blot if absorbed the next operation (some blots are made of 2 operations)
                    if ($blotInstance->hasConsumedNextOp()) {
                        $i++;
                    }

                    // Some block type blots get a group all to themselves.
                    if ($blotInstance instanceof Blots\AbstractBlockBlot && $blotInstance->isOwnGroup()) {
                        $this->groups[] = $group;
                        $group = new BlotGroup();
                    }

                    break;
                }
            }
        }
       $this->groups[] = $group;
    }

    /**
     * Render operations into HTML. Be sure to do filtering/sanitation after this!
     *
     * @return string
     */
    public function render(): string {
        $result = "";
        $previousGroupEndsWithBlockEmbed = false;
        $previousGroupIsBreakOnly = false;
        foreach ($this->groups as $index => $group) {
            $skip = false;
            $isLastPosition = $index === count($this->groups) - 1;

            if ($group->isBreakOnlyGroup()) {

                if ($previousGroupIsBreakOnly) {
                    // Skip if the this is last Break in a series of 2+ breaks.
                    if (!$isLastPosition) {
                        $nextGroup = $this->groups[$index + 1];
                        if (!$nextGroup->isBreakOnlyGroup()) {
                            $skip = true;
                        }
                    }

                    // If there are multiple breaks at the end of the delta, the last one doesn't render.
                    if ($isLastPosition) {
                        $skip = true;
                    }
                }

                // Skip the last line break unless the previous group was a block embed.
                if ($isLastPosition && !$previousGroupEndsWithBlockEmbed) {
                    $skip = true;
                }
            }

            // Update previous group values.
            $previousGroupIsBreakOnly = $group->isBreakOnlyGroup();
            $previousGroupEndsWithBlockEmbed = $group->endsWithBlotOfType(Blots\Embeds\AbstractBlockEmbedBlot::class);

            // Render unless we decided we had to sip this group.
            if (!$skip) {
                $result .= $group->render();
            }
        }

        return $result;
    }
}
