<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Test for safeUnlink().
 */

class SafeUnlinkTest extends TestCase {

    /**
     * Tests {@link safeUnlink()} against two scenarios.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        \Gdn_FileSystem::removeFolder(PATH_ROOT.'/tests/cache/safe-unlink');
        touchFolder(PATH_ROOT.'/tests/cache/safe-unlink');
    }

    /**
     * Test {@link safeUnlink()} by trying to delete a nonexistent file.
     */
    public function testSafeUnlinkPhantom() {
        $filename = PATH_ROOT.'/tests/cache/safe-unlink/phantom-file';
        $this->assertFileNotExists($filename);
        $actual = safeUnlink($filename);
        $this->assertFalse($actual);
    }

    /**
     * Test where folder is deleted.
     */
    public function testSafeUnlinkFolderExists() {
        $filename = PATH_ROOT . '/tests/cache/safe-unlink/test-file.txt';
        file_put_contents($filename, 'foo');
        $this->assertFileExists($filename);
        $actual = safeUnlink($filename);
        $this->assertTrue($actual);
        $this->assertFileNotExists($filename);
    }
}
