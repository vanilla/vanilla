<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Vanilla\FileUtils;
use VanillaTests\VanillaTestCase;

/**
 * Tests for the FileUtils class.
 */
class FileUtilsTest extends VanillaTestCase {
    /**
     * Test the basic put/get loop for saving a variable.
     */
    public function testPutGetExport(): void {
        $path = tempnam(PATH_ROOT.'/tests/cache', __FUNCTION__);

        $var = ['a' => 'b'];
        $r = FileUtils::putExport($path, $var);
        $this->assertTrue($r);

        $actual = FileUtils::getExport($path);
        $this->assertSame($var, $actual);
    }
}
