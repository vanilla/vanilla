<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use VanillaTests\Fixtures\Aliases\ExtendsNewClass;
use VanillaTests\Fixtures\Aliases\NewClass;
use VanillaTests\Fixtures\Aliases\NewClassFromNamespace;
use VanillaTests\Fixtures\Aliases\NewClassWithContainer;

class AliasAutoloader extends \Vanilla\AliasAutoloader {

    /**
     * An array of OLD_CLASS_NAME => New classname.
     */
    protected static $aliases = [
        NewClass::CLASS_ALIAS => NewClass::class,
        NewClassFromNamespace::CLASS_ALIAS => NewClassFromNamespace::class,
        NewClassWithContainer::CLASS_ALIAS => NewClassWithContainer::class,
        ExtendsNewClass::CLASS_ALIAS => ExtendsNewClass::class,
    ];
}
