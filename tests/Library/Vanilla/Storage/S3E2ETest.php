<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Storage;

use Vanilla\Storage\S3\S3StorageProvider;
use Vanilla\UploadedFile;
use VanillaTests\Library\Vanilla\UploadedFileTest;

/**
 * End-to-end tests for the S3 storage provider.
 */
class S3E2ETest extends UploadedFileTest
{
    private S3StorageProvider $s3;

    private const DEFAULT_CONFIG = [
        "Endpoint" => "http://files-api.vanilla.local",
        "Region" => "us-west-2",
        "Credentials" => [
            "Key" => "minio",
            "Secret" => "password",
        ],
        "Prefix" => "default-bucket",
        "Zone" => "us",
    ];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        self::setConfigs([
            "Garden.Storage.Provider" => "awss3",
            "S3" => self::DEFAULT_CONFIG,
        ]);
    }

    /**
     * Assert that a file was uploaded to S3.
     *
     * @param UploadedFile $file
     * @param string $message
     * @return void
     */
    protected function assertFileUploaded(UploadedFile $file, string $message = ""): void
    {
        $uploadedUrl = \Gdn_Upload::url($file->getPersistedPath());
        // Do a Http request to ensure there is a file at the url.
        $ch = curl_init($uploadedUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->assertEquals(200, $httpCode, $message);
    }

    /**
     * @param array $configs
     * @return void
     */
    public function setConfigs(array $configs): void
    {
        \Gdn::config()->saveToConfig($configs);
    }

    /**
     * S3 file storage bypasses event handling.
     */
    public function testPersistEventHandling()
    {
        $this->assertTrue(true);
    }

    /**
     * Override the default assertStringMatchesFormat method to prepend %s to the format string.
     *
     * @param string $format
     * @param string $string
     * @param string $message
     * @return void
     */
    public static function assertStringMatchesFormat(string $format, string $string, string $message = ""): void
    {
        parent::assertStringMatchesFormat("%s" . $format, $string, $message);
    }
}
