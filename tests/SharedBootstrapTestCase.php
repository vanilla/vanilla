<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Container;

/**
 * Class SharedBootstrapTestCase.
 *
 * @deprecated Use the `BootstrapTestCase` instead.
 */
class SharedBootstrapTestCase extends BootstrapTestCase {
    /**
     * Whether or not the container is "null".
     *
     * @param ?Container $container
     *
     * @return bool
     */
    private static function containerIsNull($container) {
        return $container === null || is_a($container, NullContainer::class);
    }

    /**
     * @inheritdoc
     */
    protected static function getBootstrapFolderName() {
        return 'sharedbootstrap';
    }
}
