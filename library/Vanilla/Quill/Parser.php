<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill;

use Vanilla\Quill\Blots;
use Vanilla\Quill\Blots\AbstractBlot;


class Parser {

    const BLOTS = [
        Blots\HeadingBlot::class,
        Blots\BulletedListBlot::class,
        Blots\OrderedListBlot::class,
        Blots\TextBlot::class,
    ];

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

                    if ($blotInstance->isEmpty()) {
                        continue;
                    }

                    $autoCloseBlock = $currentBlotType !== null && $currentBlotType !== $blot;

                    if ($blotInstance->shouldClearCurrentBlock($block) || $autoCloseBlock) {
                        $this->blocks[] = $block;
                        $block = new Block();
                        $currentBlotType = $blot;
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

    public function render() {
        $result = "";
        foreach ($this->blocks as $block) {
            $result .= $block->render();
        }

        // One last replace to fix the breaks.
        $result = \preg_replace("/<p><\/p>/", "<p><br></p>", $result);

        return $result;
    }
}
