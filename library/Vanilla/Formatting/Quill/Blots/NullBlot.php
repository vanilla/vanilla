<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots;

use Vanilla\Formatting\Quill\BlotGroup;

/**
 * A blot for non-matching operations. This is to prevent crashes when bad input is introduced. It renders nothing
 * and acts as if it doesn't exist.
 */
class NullBlot extends AbstractBlot {

    /**
     * The NullBlot is the ultimate fallback blot. It matches anything so always return true.
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return true;
    }

    /**
     * The null blot always has empty content.
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
        return $this->content;
    }

    /**
     * A null blot should not have any affect on anything around it.
     * @inheritDoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        return false;
    }

    /**
     * The null blot only ever consumes it's own non-matching operation.
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return false;
    }
}
