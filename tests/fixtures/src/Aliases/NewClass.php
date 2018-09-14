<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Aliases;

/**
 * Dummy class fixture.
 */
class NewClass {
}

TestAliasLoader::createAliases(NewClass::class);
