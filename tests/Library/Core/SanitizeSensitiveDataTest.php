<?php
/**
 * Tests for password hash disclosure prevention in debug mode.
 *
 * @author Vanilla Community
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Test password hash sanitization in debug output.
 *
 * @package VanillaTests\Library\Core
 */
class SanitizeSensitiveDataTest extends TestCase
{
    /**
     * Test that the sanitizeSensitiveData method masks password fields.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataMasksPasswords()
    {
        $data = [
            "Username" => "testuser",
            "Password" => "secretpassword123",
            "Email" => "test@example.com",
        ];

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($data);

        $this->assertEquals("testuser", $sanitized["Username"]);
        $this->assertEquals("*******", $sanitized["Password"]);
        $this->assertEquals("*******", $sanitized["Email"]);
    }

    /**
     * Test that the sanitizeSensitiveData method masks hashmethod fields.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataMasksHashMethod()
    {
        $data = [
            "Username" => "testuser",
            "HashMethod" => "Vanilla",
            "Email" => "test@example.com",
        ];

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($data);

        $this->assertEquals("testuser", $sanitized["Username"]);
        $this->assertEquals("*******", $sanitized["HashMethod"]);
        $this->assertEquals("*******", $sanitized["Email"]);
    }

    /**
     * Test that the sanitizeSensitiveData method works with nested data structures.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataWorksWithNestedData()
    {
        $data = [
            "User" => [
                "UserID" => 123,
                "Username" => "testuser",
                "Password" => "secretpassword123",
                "Profile" => [
                    "Email" => "test@example.com",
                    "HashMethod" => "Vanilla",
                ],
            ],
            "Config" => [
                "Database" => [
                    "password" => "dbpassword",
                ],
            ],
        ];

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($data);

        $this->assertEquals(123, $sanitized["User"]["UserID"]);
        $this->assertEquals("testuser", $sanitized["User"]["Username"]);
        $this->assertEquals("*******", $sanitized["User"]["Password"]);
        $this->assertEquals("*******", $sanitized["User"]["Profile"]["Email"]);
        $this->assertEquals("*******", $sanitized["User"]["Profile"]["HashMethod"]);
        $this->assertEquals("*******", $sanitized["Config"]["Database"]["password"]);
    }

    /**
     * Test that the sanitizeSensitiveData method is case-insensitive.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataIsCaseInsensitive()
    {
        $data = [
            "PASSWORD" => "uppercase",
            "password" => "lowercase",
            "Password" => "mixedcase",
            "HASHMETHOD" => "uppercase",
            "hashmethod" => "lowercase",
            "HashMethod" => "mixedcase",
        ];

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($data);

        $this->assertEquals("*******", $sanitized["PASSWORD"]);
        $this->assertEquals("*******", $sanitized["password"]);
        $this->assertEquals("*******", $sanitized["Password"]);
        $this->assertEquals("*******", $sanitized["HASHMETHOD"]);
        $this->assertEquals("*******", $sanitized["hashmethod"]);
        $this->assertEquals("*******", $sanitized["HashMethod"]);
    }

    /**
     * Test that the sanitizeSensitiveData method doesn't modify the original data.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataDoesNotModifyOriginal()
    {
        $originalData = [
            "Username" => "testuser",
            "Password" => "secretpassword123",
            "Email" => "test@example.com",
        ];

        // Create a copy to compare against
        $expectedOriginal = $originalData;

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($originalData);

        // Original data should remain unchanged
        $this->assertEquals($expectedOriginal, $originalData);
        // Sanitized data should have masked password
        $this->assertEquals("*******", $sanitized["Password"]);
        $this->assertEquals("testuser", $sanitized["Username"]);
    }

    /**
     * Test that the sanitizeSensitiveData method handles non-array data.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataHandlesNonArrayData()
    {
        $object = new stdClass();
        $object->Username = "testuser";
        $object->Password = "secretpassword123";

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($object);

        $this->assertTrue(is_array($sanitized));
        $this->assertEquals("testuser", $sanitized["Username"]);
        $this->assertEquals("*******", $sanitized["Password"]);
    }

    /**
     * Test that the sanitizeSensitiveData method handles empty data.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataHandlesEmptyData()
    {
        $sanitized = \Gdn_Controller::sanitizeSensitiveData([]);
        $this->assertEquals([], $sanitized);

        $sanitizedNull = \Gdn_Controller::sanitizeSensitiveData(null);
        $this->assertEquals([], $sanitizedNull);
    }

    /**
     * Test that the sanitizeSensitiveData method preserves non-sensitive data.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataPreservesNonSensitiveData()
    {
        $data = [
            "UserID" => 123,
            "Username" => "testuser",
            "Roles" => ["Admin", "Member"],
            "Settings" => [
                "Theme" => "Default",
                "Language" => "en",
            ],
        ];

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($data);

        // All non-sensitive data should be preserved exactly
        $this->assertEquals($data, $sanitized);
    }

    /**
     * Test that the sanitizeSensitiveData method masks authentication and security fields.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataMasksAuthenticationFields()
    {
        $data = [
            "Username" => "testuser",
            "TransientKey" => "csrf_token_123",
            "AccessToken" => "bearer_abc123",
            "RefreshToken" => "refresh_xyz789",
            "SessionId" => "session_456",
            "Cookie" => "vanilla_session=def456",
        ];

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($data);

        $this->assertEquals("testuser", $sanitized["Username"]);
        $this->assertEquals("*******", $sanitized["TransientKey"]);
        $this->assertEquals("*******", $sanitized["AccessToken"]);
        $this->assertEquals("*******", $sanitized["RefreshToken"]);
        $this->assertEquals("*******", $sanitized["SessionId"]);
        $this->assertEquals("*******", $sanitized["Cookie"]);
    }

    /**
     * Test that the sanitizeSensitiveData method masks all sensitive fields case-insensitively.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataMasksAllSensitiveFieldsCaseInsensitive()
    {
        $data = [
            "Username" => "testuser",
            "PASSWORD" => "secret123",
            "hashmethod" => "bcrypt",
            "TRANSIENTKEY" => "csrf_uppercase",
            "transientkey" => "csrf_lowercase",
            "ACCESSTOKEN" => "token_uppercase",
            "RefreshToken" => "refresh_mixedcase",
            "sessionid" => "session_lowercase",
            "COOKIE" => "cookie_uppercase",
        ];

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($data);

        // Verify all sensitive fields are masked regardless of case
        $this->assertEquals("testuser", $sanitized["Username"]);
        $this->assertEquals("*******", $sanitized["PASSWORD"]);
        $this->assertEquals("*******", $sanitized["hashmethod"]);
        $this->assertEquals("*******", $sanitized["TRANSIENTKEY"]);
        $this->assertEquals("*******", $sanitized["transientkey"]);
        $this->assertEquals("*******", $sanitized["ACCESSTOKEN"]);
        $this->assertEquals("*******", $sanitized["RefreshToken"]);
        $this->assertEquals("*******", $sanitized["sessionid"]);
        $this->assertEquals("*******", $sanitized["COOKIE"]);
    }

    /**
     * Test that the sanitizeSensitiveData method works with deeply nested sensitive data.
     *
     * @return void
     */
    public function testSanitizeSensitiveDataWorksWithDeeplyNestedSensitiveData()
    {
        $data = [
            "User" => [
                "UserID" => 123,
                "Auth" => [
                    "AccessToken" => "token123",
                    "Session" => [
                        "SessionId" => "session123",
                        "TransientKey" => "csrf123",
                    ],
                ],
            ],
        ];

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($data);

        $this->assertEquals(123, $sanitized["User"]["UserID"]);
        $this->assertEquals("*******", $sanitized["User"]["Auth"]["AccessToken"]);
        $this->assertEquals("*******", $sanitized["User"]["Auth"]["Session"]["SessionId"]);
        $this->assertEquals("*******", $sanitized["User"]["Auth"]["Session"]["TransientKey"]);
    }

    /**
     * Test that nested API error responses with sensitive data are properly sanitized.
     *
     * @return void
     */
    public function testSanitizeApiErrorResponseWithNestedSensitiveData()
    {
        $data = [
            "code" => 400,
            "Data" => [
                "profile" => [
                    "Password" => "thisISmyPa\$\$word",
                    "RefreshToken" => "refresh_abc123",
                    "Username" => "testuser",
                    "AccessToken" => "bearer_token_xyz",
                ],
                "tracking" => [
                    "SessionId" => "session_987654",
                    "Cookie" => "vanilla_session=abc123",
                ],
                "meta" => [
                    "nested" => [
                        "deep" => [
                            "HashMethod" => "bcrypt",
                            "TransientKey" => "csrf_token_456",
                        ],
                    ],
                ],
            ],
            "message" => "Test response",
        ];

        $sanitized = \Gdn_Controller::sanitizeSensitiveData($data);

        // Verify sensitive fields are masked
        $this->assertEquals("*******", $sanitized["Data"]["profile"]["Password"]);
        $this->assertEquals("*******", $sanitized["Data"]["profile"]["RefreshToken"]);
        $this->assertEquals("*******", $sanitized["Data"]["profile"]["AccessToken"]);
        $this->assertEquals("*******", $sanitized["Data"]["tracking"]["SessionId"]);
        $this->assertEquals("*******", $sanitized["Data"]["tracking"]["Cookie"]);
        $this->assertEquals("*******", $sanitized["Data"]["meta"]["nested"]["deep"]["HashMethod"]);
        $this->assertEquals("*******", $sanitized["Data"]["meta"]["nested"]["deep"]["TransientKey"]);

        // Verify non-sensitive fields are preserved
        $this->assertEquals(400, $sanitized["code"]);
        $this->assertEquals("testuser", $sanitized["Data"]["profile"]["Username"]);
        $this->assertEquals("Test response", $sanitized["message"]);
    }
}
