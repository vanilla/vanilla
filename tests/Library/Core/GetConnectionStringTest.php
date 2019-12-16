<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for getConnectionString().
 */

class GetConnectionStringTest extends TestCase {

    /**
     * Tests {@link getConnectionString()} against several scenarios.
     *
     * @param string $testDatabaseName The name of the database to connect to.
     * @param string $testHostName The database host.
     * @param string $testServerType The type of database server.
     * @param string $expected The expected result.
     * @dataProvider provideTestGetConnectionStringArrays
     */
    public function testGetConnectionString($testDatabaseName, $testHostName, $testServerType, $expected) {
        $actual = getConnectionString($testDatabaseName, $testHostName, $testServerType);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link getConnectionString()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestGetConnectionStringArrays() {
        $r = [
            'normalCase' => [
                'database',
                'localhost:5000',
                'mysql',
                'mysql:host=localhost;port=5000;dbname=database',
            ],
            'noPort' => [
                'database',
                'localhost',
                'mysql',
                'mysql:host=localhost;dbname=database',
            ],
            'emptyStrings' => [
                '',
                '',
                '',
                ':host=;dbname=',
            ],
        ];

        return $r;
    }
}
