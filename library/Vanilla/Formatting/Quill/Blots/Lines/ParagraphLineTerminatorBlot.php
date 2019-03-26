<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;

use Vanilla\Formatting\Quill\BlotGroup;

/**
 * A blot to represent a paragraph line terminator.
 */
class ParagraphLineTerminatorBlot extends AbstractLineTerminatorBlot {
    /**
     * Paragraph lines only newline items with no attributes.
     * @see Parser::splitPlainTextNewlines()
     * @inheritdoc
     */
    public static function matches(array $operation): bool {
        $mainOpInsert = $operation['insert'] ?? null;
        if (is_string($mainOpInsert)) {
            $mainOpMatch = preg_match("/^(\\n)+$/", $mainOpInsert);
            return $mainOpMatch && !array_key_exists("attributes", $operation);
        } else {
            return false;
        }
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
     * Paragraph line blots should always be grouped as a single line.
     * @inheritdoc
     */
    public function isOwnGroup(): bool {
        return true;
    }

    /**
     * We only use the line tags since the paragraph line is its own group.
     * @inheritdoc
     */
    public function getGroupOpeningTag(): string {
        return "";
    }

    /**
     * We only use the line tags since the paragraph line is its own group.
     * @inheritdoc
     */
    public function getGroupClosingTag(): string {
        return "";
    }

    /**
     * @inheritdoc
     */
    public function renderLineStart(): string {
        return "<p>";
    }

    /**
     * @inheritdoc
     */
    public function renderLineEnd(): string {
        return "</p>";
    }
}
