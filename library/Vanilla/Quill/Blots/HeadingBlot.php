<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\Block;

class HeadingBlot extends TextBlot {

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        $found = false;

        foreach($operations as $op) {
            if(valr("attributes.header", $op)) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    public function getHeadingLevel(): int {
        return valr("attributes.header", $this->nextOperation);
    }

    public function shouldClearCurrentBlock(Block $block): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return true;
    }

}
