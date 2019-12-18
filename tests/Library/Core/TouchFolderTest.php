<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for touchFolder().
 */

class TouchFolderTest extends TestCase {

    /**
     * Tests {@link touchFolder()} against two scenarios.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        \Gdn_FileSystem::removeFolder(PATH_ROOT.'/tests/cache/touch-folder');
        touchFolder(PATH_ROOT.'/tests/cache/touch-folder');
    }

    /**
     * Test where folder already exists.
     */
    public function testTouchFolderAlreadyThere() {
        $this->assertDirectoryExists(PATH_ROOT.'/tests/cache/touch-folder');
        touchFolder(PATH_ROOT.'/tests/cache/touch-folder');
        $this->assertDirectoryExists(PATH_ROOT.'/tests/cache/touch-folder');
    }

    /**
     * Test where folder is created.
     */
    public function testTouchFolderCreate() {
        $this->assertDirectoryNotExists(PATH_ROOT.'/tests/cache/touch-folder/inner-folder');
        touchFolder(PATH_ROOT.'/tests/cache/touch-folder/inner-folder');
        $this->assertDirectoryExists(PATH_ROOT.'/tests/cache/touch-folder/inner-folder');
    }
}
