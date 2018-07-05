<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

class HeadingBlot extends TextBlot {

    /** @var array Valid heading levels. */
    private static $validLevels = [1, 2, 3, 4, 5, 6];

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return static::operationsContainKeyWithValue($operations, "header", static::$validLevels);
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        return "<h" . $this->getHeadingLevel() . ">";
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "</h" . $this->getHeadingLevel() . ">";
    }

    /**
     * @inheritDoc
     */
    public function isOwnGroup(): bool {
        return true;
    }

    public function hasConsumedNextOp(): bool {
        return true;
    }

    /**
     * Get the heading level for the blot.
     *
     * @return int
     * @throws \Exception if the level is not a valid integer.
     */
    private function getHeadingLevel(): int {
        $level = valr("attributes.header", $this->currentOperation)
            ?: valr("attributes.header", $this->nextOperation);
        if (!in_array($level, self::$validLevels)) {
            throw new \Exception("Invalid heading level");
        }
        return $level;
    }
}
