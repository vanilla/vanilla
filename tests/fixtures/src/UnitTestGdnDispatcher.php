<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Fixtures;

/**
 * Make some **Gdn_Dispatcher** methods public for unit testing.
 */
class UnitTestGdnDispatcher extends \Gdn_Dispatcher {
    /**
     * UnitTestGdnDispatcher constructor.
     */
    public function __construct() {
    }

    /**
     * {@inheritdoc}
     */
    public function filterName($name) {
        return parent::filterName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function dashCase(string $str): string {
        return parent::dashCase($str);
    }

    /**
     * {@inheritdoc}
     */
    public function makeCanonicalUrl($controller, \ReflectionFunctionAbstract $method, $args): string {
        return parent::makeCanonicalUrl($controller, $method, $args);
    }
}
