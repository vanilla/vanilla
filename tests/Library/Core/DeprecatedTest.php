<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for deprecated().
 */
class DeprecatedTest extends TestCase
{
    /**
     * Set a custom error handler to throw exceptions.
     *
     * This is necessary because PHPUnit 10+ no longer has a decated method to assert deprecation notices.
     *
     * See: https://github.com/sebastianbergmann/phpunit/issues/5062
     *
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        set_error_handler(static function ($errno, $errstr) {
            throw new \Exception($errstr, $errno);
        }, E_ALL);
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void
    {
        parent::tearDown();
        restore_error_handler();
    }

    /**
     * Test {@link deprecated()} against two scenarios ($newName and !$newName).
     *
     * @param string $testOldName The name of the deprecated function.
     * @param string $testNewName The name of the new function that should be used instead.
     * @dataProvider provideDeprecatedArrays
     */
    public function testDeprecated(string $testOldName, string $testNewName = "")
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("$testOldName is deprecated.");
        deprecated($testOldName, $testNewName);
    }

    /**
     * Provide test data for {@link deprecated()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideDeprecatedArrays()
    {
        $r = [
            "normalCase" => ["deprecatedFunction", "newFunction"],
            "noNewName" => ["deprecatedFunction"],
        ];

        return $r;
    }
}
