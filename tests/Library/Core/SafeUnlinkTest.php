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
     * Test {@link safeUnlink()} by trying to delete a nonexistent file.
     */
    public function testSafeUnlinkPhantom() {
        $actual = safeUnlink(PATH_ROOT.'/tests/cache/safe-unlink/phantom-file');
        $expected = false;
        $this->assertSame($expected, $actual);
    }
}
