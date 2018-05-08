<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\BlotGroup;
use Vanilla\Quill\Renderer;

abstract class AbstractBlockBlot extends TextBlot {

    /** @var bool */
    private $shouldClearCurrentGroup;

    /**
     * Determine if this blot is always in its own group, or if it can should share a group with other blots.
     *
     * @return bool
     */
    abstract public function isOwnGroup(): bool;

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
        } elseif (stringBeginsWith($this->content, "\n")) {
            $this->content = \ltrim($this->content, "\n");
            $this->shouldClearCurrentGroup = true;
        } elseif (array_key_exists(BlotGroup::BREAK_MARKER, $this->currentOperation)) {
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
        $lookupKey = static::getAttributeKey();
        $expected = static::getMatchingAttributeValue();

        foreach($operations as $op) {
            $value = valr("attributes.$lookupKey", $op);

            if (
                (is_array($expected) && in_array($value, $expected))
                || $value === $expected
            ) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        return $this->shouldClearCurrentGroup;
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return $this::matches([$this->nextOperation]) && !$this::matches([$this->currentOperation]);
    }
}
