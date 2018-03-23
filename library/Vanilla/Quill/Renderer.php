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

    const GROUP_BREAK_MARKER = "group-break-marker";

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

    /** @var Group[]  */
    private $groups = [];

    /**
     * Parser constructor.
     *
     * @param array $operations
     */
    public function __construct(array $operations) {
        $this->operations = $operations;
        $this->splitNewLines();
        $this->parse();
    }

    private function splitNewLines() {
        $newOperations = [];

        foreach($this->operations as $opIndex => $op) {
            if (\array_key_exists("attributes", $op) || !\array_key_exists("insert", $op) || !is_string($op["insert"])) {
                $newOperations[] = $op;
                continue;
            }

            if ($op["insert"] === "\n") {
                $op[static::GROUP_BREAK_MARKER] = true;
                $newOperations[] = $op;
                continue;
            }

            $pieces = \explode("\n", $op["insert"]);

            if (count($pieces) > 1) {
                foreach($pieces as $index => $piece) {
                    $insert = ["insert" =>  $piece];

                    $insert[static::GROUP_BREAK_MARKER] = true;

                    $newOperations[] = $insert;
                }

//                // Also set a break marker on the next blot.
                if ($opIndex < count($this->operations) - 1) {
                    $this->operations[$opIndex + 1][static::GROUP_BREAK_MARKER] = true;
                }
            } else {
                $newOperations[] = $op;
            }
        }

        $this->operations = $newOperations;
    }

    /**
     * Parse the operations into an array of Blocks.
     */
    private function parse() {
        $operationLength = \count($this->operations);
        $group = new Group();
        $currentBlotType = null;

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

                if ($blot::matches([$currentOp, $nextOp])) {
                    /** @var AbstractBlot $blotInstance */
                    $blotInstance = new $blot($currentOp, $previousOp, $nextOp);
                    $autoCloseBlock = $currentBlotType !== null && $currentBlotType !== Blots\TextBlot::class && $blot !== Blots\TextBlot::class && $currentBlotType !== $blot;

                    if ($blotInstance->shouldClearCurrentGroup($group) || $autoCloseBlock) {
                        $this->groups[] = $group;
                        $group = new Group();
                        $currentBlotType = $blot;
                    }

                    $group->pushBlot($blotInstance);

                    if ($blotInstance->hasConsumedNextOp()) {
                        $i++;
                    }

                    if ($blotInstance instanceof Blots\AbstractBlockBlot && $blotInstance->isOwnGroup()) {
                        $this->groups[] = $group;
                        $group = new Group();
                        $currentBlotType = null;
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
        $previousGroupEndsWithInlineEmbed = false;
        $previousGroupEndsWithBlockEmbed = false;
        $previousGroupIsBreakOnly = false;
        foreach ($this->groups as $index => $group) {
            $skip = false;
            $isLastPosition = $index === count($this->groups) - 1;

            if (!$isLastPosition && $group->isBreakOnlyGroup()) {
                $nextGroup = $this->groups[$index + 1];

                // Skip if the this is last Break in a series of 2+ breaks.
                if ($previousGroupIsBreakOnly && !$nextGroup->isBreakOnlyGroup()) {
                    $skip = true;
                }
            }

            if ($isLastPosition && $previousGroupIsBreakOnly && $group->isBreakOnlyGroup()) {
                $skip = true;
            }

            if (
                $group->isBreakOnlyGroup()
                && !$previousGroupIsBreakOnly
                && !$previousGroupEndsWithBlockEmbed
                && (
                    $previousGroupEndsWithInlineEmbed
                    || $isLastPosition
                )
            ) {
                $skip = true;
            }

            $previousGroupIsBreakOnly = $group->isBreakOnlyGroup();
            $previousGroupEndsWithInlineEmbed = $group->endsWithBlotOfType(Blots\Embeds\AbstractInlineEmbedBlot::class);
            $previousGroupEndsWithBlockEmbed = $group->endsWithBlotOfType(Blots\Embeds\AbstractBlockEmbedBlot::class);

            if (!$skip) {
                $result .= $group->render();
            }
        }

        return $result;
    }
}
