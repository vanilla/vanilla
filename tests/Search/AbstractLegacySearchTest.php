<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Search;

/**
 * Search test with helpers for legacy search.
 */
abstract class AbstractLegacySearchTest extends AbstractSearchTest {

    use LegacySearchTestTrait;

    /**
     * Make sure advanced search is enabled.
     */
    public static function setupBeforeClass(): void {
        if (!in_array('AdvancedSearch', static::$addons)) {
            static::$addons[] = 'AdvancedSearch';
        }
        parent::setupBeforeClass();
    }
}
