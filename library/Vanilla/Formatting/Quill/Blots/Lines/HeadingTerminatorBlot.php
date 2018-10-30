<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;
use Vanilla\Formatting\Quill\BlotGroup;

/**
 * Blot to represent heading line terminators.
 *
 * Currently only 2 levels are allowed.
 */
class HeadingTerminatorBlot extends AbstractLineTerminatorBlot {

    /** @var array Valid heading levels. */
    private static $validLevels = [2, 3];

    /**
     * @inheritDoc
     */
    public static function matches(array $operation): bool {
        return
            static::opAttrsContainKeyWithValue($operation, "header", static::$validLevels)
            || static::opAttrsContainKeyWithValue($operation, "header.level", static::$validLevels);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function getGroupOpeningTag(): string {
        return "<h" . $this->getHeadingLevel() . ' data-id="' . $this->getReference() . '" >';
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function getGroupClosingTag(): string {
        return "</h".$this->getHeadingLevel().">";
    }

    /**
     * Since headings are always one line, they don't need special line starts or ends.
     *
     * The group tags are enough.
     */
    public function renderLineStart(): string {
        return "";
    }

    /**
     * Since headings are always one line, they don't need special line starts or ends.
     *
     * The group tags are enough.
     */
    public function renderLineEnd(): string {
        return "";
    }

    public function isOwnGroup(): bool {
        return true;
    }

    /**
     * The heading blot can be the ONLY overriding blot in a group. Even other headings.
     *
     * @param BlotGroup $group
     * @return bool
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        $overridingBlot = $group->getPrimaryBlot();
        return !!$overridingBlot;
    }

    /**
     * Get the heading level for the blot.
     *
     * @return int
     * @throws \Exception if the level is not a valid integer.
     */
    public function getHeadingLevel(): int {
        $defaultLevel = 2;
        // Heading attributes generally live in the next operation.
        // For empty headings there is only one operation, so it could be in the current op.
        return $this->currentOperation["attributes"]["header"]["level"]
            ?? $this->currentOperation["attributes"]["header"]
            ?? $defaultLevel;
    }

    /**
     * Get the unique interdoc ref id for the blot.
     *
     * @return string
     */
    public function getReference(): string {
        return $this->currentOperation["attributes"]["header"]["ref"] ?? '';
    }
}
