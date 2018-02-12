<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill;
use Vanilla\Quill\Blots\AbstractBlot;
use Vanilla\Quill\Blots\BulletedListBlot;
use Vanilla\Quill\Blots\HeadingBlot;
use Vanilla\Quill\Blots\ListBlot;
use Vanilla\Quill\Blots\OrderedListBlot;
use Vanilla\Quill\Blots\TextBlot;

/**
 * Quill Operations still need one more pass before they are easily renderable.
 */
class Block {
    /** @var AbstractBlot[] */
    private $blots = [];

    public static function makeEmptyBlock(): Block {
        $block = new Block();
        $block->blots = [
            new TextBlot(["insert" => "<br>"], [], []),
        ];
        return $block;
    }

    /**
     * Render the block.
     *
     * @return string
     */
    public function render(): string {
        if (count($this->blots) === 0) {
            return "";
        }

        $result = "<" . $this->getSurroundingTag() . ">";

        $lastBlotIndex = null;

        foreach ($this->blots as $blot) {
            $result .= $blot->render();
        }

        $result .= "</" . $this->getSurroundingTag() . ">";
        $result = $this->createLineBreaks($result);
        return $result;
    }

    public function pushBlot(AbstractBlot $blot) {
        $this->blots[] = $blot;
    }

    private function getSurroundingTag() {
        $result = "p";

        $headingIndex = $this->getIndexForBlotOfType(HeadingBlot::class);
        if ($headingIndex >= 0) {
            /** @var HeadingBlot $headingBlot */
            $headingBlot = $this->blots[$headingIndex];
            $result = "h" . $headingBlot->getHeadingLevel();
        }

        if($this->getIndexForBlotOfType(OrderedListBlot::class) >= 0) {
            $result = "ol";
        }

        if($this->getIndexForBlotOfType(BulletedListBlot::class) >= 0) {
            $result = "ul";
        }

        return $result;
    }

    public function getIndexForBlotOfType(string $blotType): int {
        $index = -1;

        foreach($this->blots as $blotIndex => $blot) {
            if(get_class($blot) === $blotType) {
                $index = $blotIndex;
                break;
            }
        }

        return $index;
    }

    private function createLineBreaks(string $input): string {
        $surroundingTag = $this->getSurroundingTag();

        $singleNewLineReplacement = "</{$surroundingTag}><{$surroundingTag}>";
        $doubleNewLineReplacement = "</{$surroundingTag}><{$surroundingTag}><br></{$surroundingTag}><{$surroundingTag}>";
        $result = \preg_replace("/[\\n]{2,}/", $doubleNewLineReplacement, $input);
        $result = \preg_replace("/\\n/", $singleNewLineReplacement, $result);
        return $result;
    }
}
