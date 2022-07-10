<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

/**
 * Interface to get recommended section IDs for widgets.
 */
interface SectionAwareInterface {
    /**
     * Get section IDs.
     *
     */
    public static function getRecommendedSectionIDs(): array;
}
