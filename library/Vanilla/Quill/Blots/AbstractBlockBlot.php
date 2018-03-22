<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\Group;

abstract class AbstractBlockBlot extends TextBlot {

    /** @var bool */
    private $shouldClearCurrentGroup;

    /**
     * Determine if this blot is always in its own group, or if it can should share a group with other blots.
     *
     * @return bool
     */
    abstract protected static function isOwnGroup(): bool;

    /**
     * Get the attribute key to check for matches on.
     *
     * @return string
     */
    abstract protected static function getAttributeKey(): string;

    /**
     * Get the expected attribute value that signifies a match.
     *
     * @return mixed
     */
    protected static function getMatchingAttributeValue() {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);

        if ($this->isOwnGroup()) {
            $this->shouldClearCurrentGroup = true;
        } elseif (\stringBeginsWith($this->content, "\n")) {
            $this->content = \ltrim($this->content, "\n");
            $this->shouldClearCurrentGroup = true;
        } else {
            $this->shouldClearCurrentGroup = false;
        }
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        $found = false;
        $key = static::getAttributeKey();
        $value = static::getMatchingAttributeValue();

        foreach($operations as $op) {
            if(valr("attributes.$key", $op) === $value) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentBlock(Group $block): bool {
        return $this->shouldClearCurrentGroup;
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return true;
    }
}
