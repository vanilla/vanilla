<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Formatting\Quill\Blots;

/**
 * Blot to represent headings.
 *
 * Currently only 2 levels are allowed.
 */
class HeadingBlot extends TextBlot {

    /** @var array Valid heading levels. */
    private static $validLevels = [1, 2];

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return static::opAttrsContainKeyWithValue($operations, "header", static::$validLevels);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function getGroupOpeningTag(): string {
        return "<h" . $this->getHeadingLevel() . ">";
    }

    /**
     * @inheritdoc
     * @throws \Exception
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

    /**
     * Get the heading level for the blot.
     *
     * @return int
     * @throws \Exception if the level is not a valid integer.
     */
    private function getHeadingLevel(): int {
        // Heading attributes live in the next operation.
        $level = $this->nextOperation["attributes"]["header"] ?? null;
        if (!in_array($level, self::$validLevels)) {
            throw new \Exception("Invalid heading level");
        }
        return $level;
    }
}
