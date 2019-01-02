<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\DelimitedScheme;

/**
 * Tests for Vanilla\Utility\NameScheme classes.
 */
class NameSchemeTest extends SharedBootstrapTestCase {
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
     * Test a basic delimited scheme.
     */
    public function testDelimitedName() {
        $names = new DelimitedScheme('.', new CamelCaseScheme());
        $name = 'Foo.Bar.BazBam';

        $converted = $names->convert($name);
        $this->assertEquals('foo.bar.bazBam', $converted);
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
