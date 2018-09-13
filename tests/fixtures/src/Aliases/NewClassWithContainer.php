<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Aliases;

class_alias(NewClassWithContainer::class, NewClassWithContainer::CLASS_ALIAS);

class NewClassWithContainer {
    const CLASS_ALIAS = "\OldClassWithContainer";
}
