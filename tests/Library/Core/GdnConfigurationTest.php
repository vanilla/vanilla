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
class GdnConfigurationTest extends TestCase
{
    /**
     * Get a new configuration instance to test.
     *
     * @return \Gdn_Configuration
     */
    private function config(): \Gdn_Configuration
    {
        $config = new \Gdn_Configuration();
        $config->autoSave(false);
        return $config;
    }

    /**
     * Test if some configuraton key exists.
     *
     * @param array $configData
     * @param string $keyToCheck
     * @param bool $expectedResult
     *
     * @dataProvider provideConfigKeyExistsData
     */
    public function testConfigKeyExists(array $configData, string $keyToCheck, bool $expectedResult)
    {
        $config = $this->config();
        $config->loadArray($configData, "test");

        $this->assertSame($expectedResult, $config->configKeyExists($keyToCheck));
    }

    /**
     * Provide test cases.
     *
     * @return array
     */
    public function provideConfigKeyExistsData(): array
    {
        return [
            "Value is false" => [["Nested" => ["Value" => false]], "Nested.Value", true],
            "Value is falsy number" => [["Nested" => ["Value" => 0]], "Nested.Value", true],
            "Value is falsy array" => [["Nested" => ["Value" => []]], "Nested.Value", true],
            "Value is falsy string" => [["Nested" => ["Value" => ""]], "Nested.Value", true],
            "Value is undefined" => [[], "Nested.Value", false],
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
    public function testConfigGet(array $configData, string $keyToCheck, $expectedResult)
    {
        $config = $this->config();
        $config->loadArray($configData, "test");

        $this->assertSame($expectedResult, $config->get($keyToCheck));
    }

    /**
     * Provide test cases.
     *
     * @return array
     */
    public function provideConfigGetData(): array
    {
        return [
            "Value is false" => [["Nested" => ["Value" => false]], "Nested.Value", false],
            "Value is falsy number" => [["Nested" => ["Value" => 0]], "Nested.Value", 0],
            "Value is falsy array" => [["Nested" => ["Value" => []]], "Nested.Value", []],
            "Value is falsy string" => [["Nested" => ["Value" => ""]], "Nested.Value", ""],
            "Value is undefined" => [[], "Nested.Value", false],
            "Value is empty string" => [[], "", false],
        ];
    }

    /**
     * Test the touch config method.
     */
    public function testTouchConfig()
    {
        // Quick check with falsy value.
        $config = $this->config();
        $config->loadArray(["Nested" => ["Value" => false]], "test");
        $config->touch("Nested.Value", "myValue");

        $this->assertEquals(false, $config->get("Nested.Value"));

        // Actually touching a value.
        $config->touch("Other.Value", "myValue");

        $this->assertEquals("myValue", $config->get("Other.Value"));
    }

    /**
     * Assert that you can touch multiple values and they will not be persisted.
     */
    public function testTouchMultipleNoPersist()
    {
        // Quick check with falsy value.
        $config = $this->config();
        $config->loadArray(["Nested" => ["Value" => "existing"]], "test");
        $config->touch(
            [
                "Nested.Value" => "myValue",
                "Other.Value" => "otherValue",
            ],
            null,
            false
        );

        $this->assertEquals("existing", $config->get("Nested.Value"));
        $this->assertEquals("otherValue", $config->get("Other.Value"));

        $dynamic = $config->getDynamic();
        $this->assertEquals("existing", $dynamic->get("Nested.Value"));

        // Value will not be persisted.
        $this->assertEquals(false, $dynamic->get("Other.Value"));
    }

    /**
     * Test config renaming.
     */
    public function testRenameConfigs()
    {
        $config = $this->config();
        $config->loadArray(
            [
                "old" => [
                    "nestedOld" => false,
                    "arrayOld" => [1, 2, 3],
                    "objectOld" => ["hello" => "world"],
                ],
            ],
            "test"
        );

        $config->renameConfigKeys([
            "old.objectOld" => "new.objectNew",
            "old.arrayOld" => "new.arrayNew",
            "old" => "newGroup",
        ]);

        $this->assertEquals(
            [
                "new" => [
                    "objectNew" => ["hello" => "world"],
                    "arrayNew" => [1, 2, 3],
                ],
                "newGroup" => [
                    "nestedOld" => false,
                ],
            ],
            $config->get(".")
        );
    }
}
