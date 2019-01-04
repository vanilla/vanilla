<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Aliases;

use Vanilla\AliasProviderTrait;

/**
 * A test fixture for testing the AliasProviderTrait.
 */
class TestAliasLoader {

    use AliasProviderTrait;

    /**
     * @inheritdoc
     */
    protected static function provideAliases(): array {
        return [
            NewClass::class => ["VanillaTests\OldClass"],
            ExtendsNewClass::class => ["VanillaTests\ExtendsOldClass"],
        ];
    }
}
