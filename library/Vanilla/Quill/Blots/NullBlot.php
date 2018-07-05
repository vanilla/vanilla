<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\BlotGroup;

/**
 * A blot for non-matching operations. This is to prevent crashes when bad input is introduced. It renders nothing
 * and acts as if it doesn't exist.
 */
class NullBlot extends AbstractBlot {

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return true;
    }

    public function isOwnGroup(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);
        $this->content = "";
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return false;
    }
}
