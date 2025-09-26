<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Vanilla\EnvUtils;
use VanillaTests\VanillaTestCase;

/**
 * Tests for {@link EnvUtils}
 */
class EnvUtilsTest extends VanillaTestCase
{
    private string $baseEnvContent;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        // Define a base .env content used in multiple tests
        $this->baseEnvContent = <<<ENV
# App Settings
APP_NAME=My App
APP_ENV=local
APP_DEBUG=true

# Database
DB_HOST=127.0.0.1
DB_PASSWORD=

# Exported Var
export CACHE_DRIVER=file

SPACED_VAR = old value
QUOTED_VAR="some value"
NO_VALUE_VAR=
ENV;
    }

    /**
     * Test that the function can update existing values in the .env file.
     */
    public function testUpdatesExistingValues(): void
    {
        $updates = [
            "APP_ENV" => "production",
            "APP_DEBUG" => false, // Test boolean conversion
            "DB_PASSWORD" => "secret",
        ];

        $expected = <<<ENV
# App Settings
APP_NAME=My App
APP_ENV=production
APP_DEBUG=false

# Database
DB_HOST=127.0.0.1
DB_PASSWORD=secret

# Exported Var
export CACHE_DRIVER=file

SPACED_VAR = old value
QUOTED_VAR="some value"
NO_VALUE_VAR=
ENV;

        $this->assertEquals($expected, EnvUtils::updateEnvFileContents($this->baseEnvContent, $updates));
    }

    /**
     * Test that the function can add new values to the .env file.
     */
    public function testAddsNewValues(): void
    {
        $updates = [
            "NEW_VAR" => "new_value",
            "ANOTHER_NEW" => 12345, // Test integer conversion
        ];

        // Expect new vars appended, preceded by a blank line if needed
        $expected = <<<ENV
# App Settings
APP_NAME=My App
APP_ENV=local
APP_DEBUG=true

# Database
DB_HOST=127.0.0.1
DB_PASSWORD=

# Exported Var
export CACHE_DRIVER=file

SPACED_VAR = old value
QUOTED_VAR="some value"
NO_VALUE_VAR=

NEW_VAR=new_value
ANOTHER_NEW=12345
ENV;

        $this->assertEquals($expected, EnvUtils::updateEnvFileContents($this->baseEnvContent, $updates));
    }

    /**
     * Test that the function can add new values to an empty .env file.
     */
    public function testAddsNewValuesToEmptyFile(): void
    {
        $initialContent = "";
        $updates = [
            "FIRST_VAR" => "hello",
            "SECOND_VAR" => "world",
        ];

        $expected = <<<ENV
FIRST_VAR=hello
SECOND_VAR=world
ENV;
        // Note: No preceding blank line when adding to an empty file.

        $this->assertEquals($expected, EnvUtils::updateEnvFileContents($initialContent, $updates));
    }

    /**
     * Test that the function can add new values to a file with only comments.
     */
    public function testAddsNewValuesToFileWithOnlyComments(): void
    {
        $initialContent = <<<ENV
# Comment 1
# Comment 2

ENV;
        $updates = [
            "FIRST_VAR" => "hello",
        ];

        $expected = <<<ENV
# Comment 1
# Comment 2

FIRST_VAR=hello
ENV;
        // Expect a blank line before the new var

        $this->assertEquals($expected, EnvUtils::updateEnvFileContents($initialContent, $updates));
    }

    /**
     * Test that the function can update and add values at the same time.
     */
    public function testUpdatesAndAddsValues(): void
    {
        $updates = [
            "APP_NAME" => "Updated App",
            "NEW_SERVICE_URL" => "https://example.com",
            "CACHE_DRIVER" => "redis", // Update exported var
            "SPACED_VAR" => "new spaced value", // Update spaced var
            "NO_VALUE_VAR" => "has value now", // Update var with no initial value
        ];

        $expected = <<<ENV
# App Settings
APP_NAME=Updated App
APP_ENV=local
APP_DEBUG=true

# Database
DB_HOST=127.0.0.1
DB_PASSWORD=

# Exported Var
export CACHE_DRIVER=redis

SPACED_VAR=new spaced value
QUOTED_VAR="some value"
NO_VALUE_VAR=has value now

NEW_SERVICE_URL=https://example.com
ENV;

        $this->assertEquals($expected, EnvUtils::updateEnvFileContents($this->baseEnvContent, $updates));
    }

    /**
     * Test that the function preserves comments and blank lines.
     */
    public function testPreservesCommentsAndBlankLines(): void
    {
        $content = <<<ENV
# Comment Line 1

VAR1=value1
# Comment Line 2

VAR2=value2

# Comment Line 3
ENV;
        $updates = [
            "VAR1" => "new_value1",
            "VAR2" => "new_value2",
            "VAR3" => "new_value3",
        ];

        $expected = <<<ENV
# Comment Line 1

VAR1=new_value1
# Comment Line 2

VAR2=new_value2

# Comment Line 3

VAR3=new_value3
ENV;

        $this->assertEquals($expected, EnvUtils::updateEnvFileContents($content, $updates));
    }

    /**
     * Test that the function handles different spacing around the equals sign.
     */
    public function testHandlesDifferentSpacingAroundEquals(): void
    {
        $content = <<<ENV
VAR_NORMAL=normal
VAR_SPACE_AFTER = after
VAR_SPACE_BEFORE= before
VAR_SPACE_BOTH = both
export   EXPORTED_SPACED  =  exported_spaced
ENV;
        $updates = [
            "VAR_NORMAL" => "new_normal",
            "VAR_SPACE_AFTER" => "new_after",
            "VAR_SPACE_BEFORE" => "new_before",
            "VAR_SPACE_BOTH" => "new_both",
            "EXPORTED_SPACED" => "new_exported_spaced",
        ];

        $expected = <<<ENV
VAR_NORMAL=new_normal
VAR_SPACE_AFTER=new_after
VAR_SPACE_BEFORE=new_before
VAR_SPACE_BOTH=new_both
export   EXPORTED_SPACED=new_exported_spaced
ENV;
        // Note: The function intentionally trims trailing space before '=' when updating
        // but preserves leading space and 'export'.

        $this->assertEquals($expected, EnvUtils::updateEnvFileContents($content, $updates));
    }

    /**
     * Test that the function handles setting an empty value.
     */
    public function testHandlesSettingEmptyValue(): void
    {
        $updates = [
            "APP_NAME" => "", // Set to empty string
            "DB_HOST" => "",
        ];

        $expected = <<<ENV
# App Settings
APP_NAME=
APP_ENV=local
APP_DEBUG=true

# Database
DB_HOST=
DB_PASSWORD=

# Exported Var
export CACHE_DRIVER=file

SPACED_VAR = old value
QUOTED_VAR="some value"
NO_VALUE_VAR=
ENV;

        $this->assertEquals($expected, EnvUtils::updateEnvFileContents($this->baseEnvContent, $updates));
    }

    /**
     * Test that the makes no changes when the update array is empty.
     */
    public function testNoChangesWhenUpdateArrayIsEmpty(): void
    {
        $updates = [];
        $this->assertEquals($this->baseEnvContent, EnvUtils::updateEnvFileContents($this->baseEnvContent, $updates));
    }

    /**
     * Test that the function throws on invalid keys when adding new values.
     */
    public function testThrowsOnInvalidKeysWhenAdding(): void
    {
        $updates = [
            "VALID_KEY" => "valid",
            "INVALID-KEY" => "invalid", // Contains hyphen
            "1NUMBER_START" => "invalid", // Starts with number
            "VALID_AGAIN" => "ok",
        ];

        $this->expectExceptionMessage("Invalid environment variable name: INVALID-KEY");
        EnvUtils::updateEnvFileContents($this->baseEnvContent, $updates);
    }

    /**
     * Test that the function handles quoted values in the original content.
     */
    public function testHandlesQuotedValuesInOriginal(): void
    {
        // The function doesn't specifically parse quotes, it just replaces the value part.
        $updates = [
            "QUOTED_VAR" => 'new "quoted" value',
        ];

        $expected = <<<ENV
# App Settings
APP_NAME=My App
APP_ENV=local
APP_DEBUG=true

# Database
DB_HOST=127.0.0.1
DB_PASSWORD=

# Exported Var
export CACHE_DRIVER=file

SPACED_VAR = old value
QUOTED_VAR=new "quoted" value
NO_VALUE_VAR=
ENV;
        // Note: The original quotes are part of the value being replaced.
        // The new value is inserted as-is.
        $this->assertEquals($expected, EnvUtils::updateEnvFileContents($this->baseEnvContent, $updates));
    }

    /**
     * Test parsing of environment variables from a string.
     *
     * @param string $contents
     * @param array $expected
     * @return void
     *
     * @dataProvider provideEnvFile
     */
    public function testParseEnvFile(string $contents, array $expected): void
    {
        $actual = EnvUtils::parseEnvFile($contents);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array[]
     */
    public function provideEnvFile(): array
    {
        return [
            "basic" => [
                <<<ENV
A=B
C=D
ENV
                ,
                ["A" => "B", "C" => "D"],
            ],
            "comments" => [
                <<<ENV
# This is a comment
A=B
# Another comment
C=D # A comment at the end
ENV
                ,
                ["A" => "B", "C" => "D"],
            ],
            "empty" => ["", []],
            "quoted values" => [
                <<<ENV
A="Do a thing \"nested\" quote"
ENV
                ,
                ["A" => 'Do a thing "nested" quote'],
            ],
        ];
    }
}
