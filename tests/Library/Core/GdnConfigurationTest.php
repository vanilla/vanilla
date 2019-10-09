<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for Gdn_Configuration.
 */
class GdnConfigurationTest extends TestCase {

    /**
     * Test if some configuraton key exists.
     *
     * @param array $configData
     * @param string $keyToCheck
     * @param bool $expectedResult
     *
     * @dataProvider provideConfigKeyExistsData
     */
    public function testConfigKeyExists(array $configData, string $keyToCheck, bool $expectedResult) {
        $config = new \Gdn_Configuration();
        $config->loadArray($configData, "test");

        $this->assertEquals($expectedResult, $config->configKeyExists($keyToCheck));
    }

    /**
     * Provide test cases.
     *
     * @return array
     */
    public function provideConfigKeyExistsData(): array {
        return [
            "Value is false" => [
                ["Nested" => ["Value" => false]],
                'Nested.Value',
                true,
            ],
            "Value is falsy number" => [
                ["Nested" => ["Value" => 0]],
                'Nested.Value',
                true,
            ],
            "Value is falsy array" => [
                ["Nested" => ["Value" => []]],
                'Nested.Value',
                true,
            ],
            "Value is falsy string" => [
                ["Nested" => ["Value" => ""]],
                'Nested.Value',
                true,
            ],
            "Value is undefined" =>[
                [],
                'Nested.Value',
                false,
            ],
        ];
    }

    /**
     * Test if some configuraton key exists.
     *
     * @param array $configData
     * @param string $keyToCheck
     * @param bool $expectedResult
     *
     * @dataProvider provideConfigGetData
     */
    public function testConfigGet(array $configData, string $keyToCheck, $expectedResult) {
        $config = new \Gdn_Configuration();
        $config->loadArray($configData, "test");

        $this->assertEquals($expectedResult, $config->get($keyToCheck));
    }

    /**
     * Provide test cases.
     *
     * @return array
     */
    public function provideConfigGetData(): array {
        return [
            "Value is false" => [
                ["Nested" => ["Value" => false]],
                'Nested.Value',
                false,
            ],
            "Value is falsy number" => [
                ["Nested" => ["Value" => 0]],
                'Nested.Value',
                0,
            ],
            "Value is falsy array" => [
                ["Nested" => ["Value" => []]],
                'Nested.Value',
                [],
            ],
            "Value is falsy string" => [
                ["Nested" => ["Value" => ""]],
                'Nested.Value',
                "",
            ],
            "Value is undefined" =>[
                [],
                'Nested.Value',
                false,
            ],
        ];
    }

    /**
     * Test the touch config method.
     */
    public function testTouchConfig() {
        // Quick check with falsy value.
        $config = new \Gdn_Configuration();
        $config->loadArray(["Nested" => ["Value" => false]], "test");
        $config->touch("Nested.Value", "myValue");

        $this->assertEquals(false, $config->get("Nested.Value"));

        // Actually touching a value.
        $config->touch("Other.Value", "myValue");

        $this->assertEquals("myValue", $config->get("Other.Value"));
    }
}
