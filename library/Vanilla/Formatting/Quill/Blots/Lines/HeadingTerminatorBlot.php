<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;
use Vanilla\Formatting\Quill\BlotGroup;

/**
 * Blot to represent heading line terminators.
 *
 * Levels 2-5 are allowed.
 */
class HeadingTerminatorBlot extends AbstractLineTerminatorBlot {

    /** @var array Valid heading levels. */
    const VALID_LEVELS = [2, 3, 4, 5];

    /** @var int the default heading level if a none is provided. */
    const DEFAULT_LEVEL = 2;

    /**
     * @inheritDoc
     */
    public static function matches(array $operation): bool {
        return
            static::opAttrsContainKeyWithValue($operation, "header", self::VALID_LEVELS)
            || static::opAttrsContainKeyWithValue($operation, "header.level", self::VALID_LEVELS);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function getGroupOpeningTag(): string {
        $ref = htmlspecialchars($this->getReference());
        $idTag = $ref ? ' data-id="' . $ref . '"' : "";
        $level = $this->getHeadingLevel();
        return "<h$level$idTag>";
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
        $overridingBlot = $group->getOverrideBlot();
        return !!$overridingBlot;
    }

    /**
     * Get the heading level for the blot.
     *
     * @return int
     */
    public function getHeadingLevel(): int {
        // Heading attributes generally live in the next operation.
        // For empty headings there is only one operation, so it could be in the current op.
        return $this->currentOperation["attributes"]["header"]["level"]
            ?? $this->currentOperation["attributes"]["header"]
            ?? self::DEFAULT_LEVEL;
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
