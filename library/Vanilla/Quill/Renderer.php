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
        Blots\HeadingBlot::class,
        Blots\BulletedListBlot::class,
        Blots\OrderedListBlot::class,
        Blots\Embeds\EmojiBlot::class,
        Blots\Embeds\ImageBlot::class,
        Blots\TextBlot::class,
    ];

    /** @var array[] */
    private $operations = [];

    /** @var Block[]  */
    private $blocks = [];

    /**
     * Parser constructor.
     *
     * @param array $operations
     */
    public function __construct(array $operations) {
        $this->operations = $operations;
        $this->parse();
    }

    /**
     * Parse the operations into an array of Blocks.
     */
    public function parse() {
        $operationLength = \count($this->operations);
        $block = new Block();
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

                    $isLastNewLine = $i === $operationLength - 1 && \preg_match("/\\n$/", $blotInstance->getContent());
                    if ($isLastNewLine) {
                        $blotInstance->setContent(\preg_replace("/\\n$/", "", $blotInstance->getContent()));
                    }

                    $autoCloseBlock = $currentBlotType !== null && $currentBlotType !== $blot;

                    if ($blotInstance->shouldClearCurrentBlock($block) || $autoCloseBlock) {
                        $this->blocks[] = $block;
                        $block = new Block();
                        $currentBlotType = $blot;
                    }

                    if (is_a($blotInstance, Blots\AbstractListBlot::class) && $blotInstance->shouldClearCurrentBlock($block)) {
                        $this->blocks[] = Block::makeEmptyBlock();
                    }

                    $block->pushBlot($blotInstance);

                    if ($blotInstance->hasConsumedNextOp()) {
                        $i++;
                    }

                    break;
                }
            }
        }
        $this->blocks[] = $block;
    }

    /**
     * Render operations into HTML. Be sure to do filtering/sanitation after this!
     *
     * @return string
     */
    public function render(): string {
        $result = "";
        foreach ($this->blocks as $block) {
            $result .= $block->render();
        }

        // One last replace to fix the breaks.
        $result = \preg_replace("/<p><\/p>/", "<p><br></p>", $result);

        return $result;
    }
}
