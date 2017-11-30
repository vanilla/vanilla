<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Utility;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\CamelCaseScheme;

/**
 * Tests for Vanilla\Utility\NameScheme classes.
 */
class NameSchemeTest extends TestCase {
    /**
     * Test some camel case scheme cases.
     *
     * @param string $name The name to convert.
     * @param string $expected The expected result.
     * @dataProvider provideCamelCaseNames
     */
    public function testCamelCaseScheme($name, $expected) {
        $names = new CamelCaseScheme();
        $actual = $names->convert($name);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Provide some basic camel case scheme tests.
     *
     * @return array Returns a data provider array.
     */
    public function provideCamelCaseNames() {
        $r = [
            ['RoleId', 'roleID'],
            ['role_id', 'roleID'],
            ['RoleIds', 'roleIDs'],
        ];

        return array_column($r, null, 1);
    }
}
