<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Patrick Desjardins <patrick.d@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;

/**
 * Tests for `validateUsername()` and `validateUsernameRegex()`.
 */
class ValidateUsernameTest extends SharedBootstrapTestCase {
    protected const CONFIG_KEYS = [
        'Garden.User'
    ];

    /**
     * @var \Gdn_Configuration
     */
    private $config;

    /**
     * @var array
     */
    private $configBak;

    /**
     * Back up the config before each test.
     */
    public function setUp(): void {
        parent::setUp();

        /* @var \Gdn_Configuration $config */
        $this->config = $this->container()->get(\Gdn_Configuration::class);

        // Back up the config before each test.
        $this->configBak = [];
        foreach (self::CONFIG_KEYS as $key) {
            $this->configBak[$key] = $this->config->get($key);
        }
    }

    /**
     * Restore the config after each test.
     */
    public function tearDown(): void {
        parent::tearDown();

        foreach ($this->configBak as $key => $value) {
            $this->config->set($key, $value, true, false);
        }
    }

    /**
     * Test `Garden.User.ValidationRegex`.
     *
     * @param string $pattern The pattern to test.
     * @param string $username The username to test.
     * @param bool $expected The expected result of `validateUsername()`.
     * @dataProvider providePartialRegexTests
     */
    public function testValidationRegex(string $pattern, string $username, bool $expected): void {
        $this->config->set('Garden.User.ValidationRegex', $pattern, true, false);
        $this->config->set('Garden.User.ValidationLength', '{3,20}', true, false);

        $actual = validateUsername($username);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test `Garden.User.ValidationRegexPattern`.
     *
     * @param string $pattern
     * @param string $username
     * @param bool $expected
     * @dataProvider provideValidationRegexPatternTests
     */
    public function testValidationRegexPattern(string $pattern, string $username, bool $expected): void {
        $this->config->set('Garden.User.ValidationRegexPattern', $pattern, true, false);

        $actual = validateUsername($username);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test `Garden.User.ValidationLength`.
     *
     * @param string $length
     * @param string $username
     * @param bool $expected
     * @dataProvider providePartialLengthTests
     */
    public function testValidationLength(string $length, string $username, bool $expected) {
        $this->config->set('Garden.User.ValidationLength', $length, true, false);

        $actual = validateUsername($username);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide tests for partial regex configs.
     *
     * @return array
     */
    public function providePartialRegexTests(): array {
        $r = [
            'valid' => ['^0-9', 'todd', true],
            'valid unescaped backtick' => ['^`', 'patches', true],
            'valid escaped backtick' => ['^\\`', 'patches', true],
            'invalid' => ['^0-9', 'todd0', false],
            'invalid slash' => ['^/', 'to/dd', false],
            'invalid unescaped backtick' => ['^`+', 'p`tches', false],
            'invalid escaped backtick' => ['^\\`+', 'p`tches', false],
        ];

        return $r;
    }

    /**
     * Provide tests for full regex configs.
     *
     * @return array
     */
    public function provideValidationRegexPatternTests(): array {
        $r = [
            'valid' => ['`[tod]+`', 'todd', true],
            'invalid' => ['`(\w+){3,30}`', 'sh', false],
        ];

        return $r;
    }

    /**
     * Provide tests for partial length regex configs.
     *
     * @return array
     */
    public function providePartialLengthTests(): array {
        $r = [
            'valid' => ['{2,4}', 'todd', true],
            'invalid' => ['{3}', 'todd', false],
        ];

        return $r;
    }
}
