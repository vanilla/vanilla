<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Gdn;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Exception;
use Garden\Container\Container;


/**
 * Class SharedBootstrapTestCase.
 */
class SharedBootstrapTestCase extends TestCase {

    use BootstrapTrait {
        setUpBeforeClass as bootstrapSetupBeforeClass;
    }

    /**
     * Bootstrap the first test cases and reuse the same container/bootstrap for subsequent test cases.
     */
    public static function setUpBeforeClass() {
        /** @var Container $currentContainer */
        $currentContainer = Gdn::getContainer();

        $containerCorruption = false;

        if (self::$container === null) {
            if (!self::containerIsNull($currentContainer)) {
                $containerCorruption = true;
            } else {
                self::bootstrapSetupBeforeClass();
            }
        } else {
            if (!self::containerIsNull($currentContainer) && $currentContainer !== self::$container) {
                $containerCorruption = true;
            } else {
                self::bootstrap()->setGlobals(self::$container);
            }
        }

        if ($containerCorruption) {
            throw new Exception('A container has not been properly cleaned by a previous test!');
        }
    }

    /**
     * Whether or not the container is "null".
     *
     * @param $container
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

    /**
     * Cleanup the container after testing is done.
     */
    public static function tearDownAfterClass() {
        Bootstrap::cleanUpGlobals();
    }
}
