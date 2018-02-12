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
use Vanilla\Quill\Blots\OrderedListBlot;
use Vanilla\Quill\Blots\TextBlot;

/**
 * Class to represent a group of a quill blots. One block can contain multiple inline type blots.
 *
 * @package Vanilla\Quill
 */
class Block {

    /** @var AbstractBlot[] */
    private $blots = [];

    /**
     * Create any empty block. When rendered it will output <p><br></p>
     *
     * @return Block
     */
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
        return $result;
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
     * Determine the html tag the surrounds the block.
     *
     * @return string
     */
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

    /**
     * Get the position in the blots array of the first blot of the given type. Defaults to -1.
     *
     * @param string $blotType
     *
     * @return int
     */
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
}
