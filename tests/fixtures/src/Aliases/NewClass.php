<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Aliases;

class_alias(NewClass::class, NewClass::CLASS_ALIAS);

class NewClass {
    const CLASS_ALIAS = "\OldClass";
}
