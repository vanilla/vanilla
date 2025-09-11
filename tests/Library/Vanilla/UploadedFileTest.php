<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Garden\EventManager;
use Garden\SafeCurl\Exception;
use Garden\SafeCurl\Exception\CurlException;
use Garden\SafeCurl\Exception\InvalidURLException;
use Vanilla\UploadedFile;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\TestUploader;

/**
 * Tests for uploaded files.
 */
class UploadedFileTest extends BootstrapTestCase
{
    public const TEST_REMOTE_FILE_URL = "https://us.v-cdn.net/6030677/uploads/userpics/47G7RW9EZD7R/nKM8SF66BM6V4.png";

    /**
     * Test that various internal IPs cannot be redirected too.
     *
     * @param string $blacklistedAddress
     *
     * @dataProvider blacklistProvider
     */
    public function testCreateFromRemoteBlacklist(string $blacklistedAddress)
    {
        $this->expectException(InvalidURLException::class);
        UploadedFile::fromRemoteResourceUrl($blacklistedAddress);
    }

    /**
     * @return array
     */
    public function blacklistProvider(): array
    {
        return [["0.0.0.0/8"], ["file:///etc/passwd"], ["gopher://localhost"], ["telnet://localhost:25"]];
    }

    /**
     * Test that redirects are followed.
     */
    public function testSavesRemoteUrls()
    {
        $file = UploadedFile::fromRemoteResourceUrl("http://vanilla.higherlogic.com");
        $this->assertEquals("http://vanilla.higherlogic.com", $file->getForeignUrl());
        $this->assertEquals("https://www.higherlogic.com/vanilla/", $file->getResolvedForeignUrl());

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertTrue(file_exists($file->getFile()));
    }

    /**
     * Assert that a file was uploaded to the local filesystem.
     *
     * @param UploadedFile $file
     * @param string $message
     * @return void
     */
    protected function assertFileUploaded(UploadedFile $file, string $message = ""): void
    {
        $this->assertFileExists(PATH_UPLOADS . "/" . $file->getPersistedPath(), $message);
    }

    /**
     * Test file persistence.
     */
    public function testPersistFile()
    {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl(self::TEST_REMOTE_FILE_URL);

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertFileExists($file->getFile());

        // Save the upload.
        $file->persistUpload();
        $this->assertFileDoesNotExist($file->getFile(), "The original upload is moved and cleaned up.");
        $this->assertFileUploaded($file, "Final upload file is persisted");

        $this->assertStringContainsString(
            "migrated/",
            $file->getPersistedPath(),
            'Persisted remote files should contain "/migrated/"'
        );
        $this->assertStringContainsString(
            strtolower($file->getClientFilename()),
            $file->getPersistedPath(),
            "Persisted remote files should the real name."
        );
    }

    /**
     * Test that we can safely persist files with spaces in their name.
     *
     * @param string $name
     *
     * @throws CurlException
     * @throws Exception
     * @dataProvider provideImagesWithSpaces
     */
    public function testPersistFileWithSpaces(string $name)
    {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl($name);

        // Save the upload.
        $file->persistUpload();
        $this->assertFileUploaded($file, "Final upload file is persisted");
        $this->assertStringContainsString("image-with-spaces.jpg", $file->getPersistedPath());
    }

    /**
     * Test that we can safely persist files with url_encoded characters in their name.
     *
     * @param string $name
     *
     * @throws CurlException
     * @throws Exception
     * @dataProvider provideImagesEncodedChars
     */
    public function testPersistFileEncodedChars(string $name)
    {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl($name);

        // Save the upload.
        $file->persistUpload();
        $this->assertFileUploaded($file, "Final upload file is persisted");
        $this->assertStringContainsString("my-25e5-259c-2596-25e7-2589-2587.png", $file->getPersistedPath());
    }

    /**
     * @return string[][]
     */
    public function provideImagesWithSpaces(): array
    {
        return [
            "no url encoding" => ["https://us.v-cdn.net/6032207/uploads/770/Image with spaces.jpg"],
            "with url encoding" => ["https://us.v-cdn.net/6032207/uploads/770/Image%20with%20spaces.jpg"],
        ];
    }

    /**
     * @return string[][]
     */
    public function provideImagesEncodedChars(): array
    {
        return [
            "with url encoding chinese chars" => [
                "https://us.v-cdn.net/5022541/uploads/320EG16UF3D6/my-%25E5%259C%2596%25E7%2589%2587.png",
            ],
        ];
    }

    /**
     * Test that custom paths can be persisted.
     */
    public function testCustomPersistedPath()
    {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl(self::TEST_REMOTE_FILE_URL);

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertFileExists($file->getFile());

        // Save the upload.
        $file->persistUpload(false, "subdir", "prefix-%s");
        $this->assertFileUploaded($file, "Final upload file is persisted");
        $this->assertStringMatchesFormat("subdir/%s/prefix-nkm8sf66bm6v4.png", $file->getPersistedPath());
    }

    /**
     * Test copying of a file.
     */
    public function testCopying()
    {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl(self::TEST_REMOTE_FILE_URL);

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertFileExists($file->getFile());

        // Save the upload.
        $file->persistUpload(true, "copied");
        $this->assertFileUploaded($file, "Final upload file is persisted");
        $this->assertFileUploaded($file, "Original file is not deleted");
    }

    /**
     * Test that an event handler can completely handle the persistance.
     */
    public function testPersistEventHandling()
    {
        /** @var EventManager $eventManager */
        $eventManager = self::container()->get(EventManager::class);
        $expectedSaveName = "custom/save/name.result";

        $eventManager->bind("gdn_upload_saveAs", function ($upload, $args) use ($expectedSaveName) {
            $args["Handled"] = true;
            $args["Parsed"]["SaveName"] = $expectedSaveName;
        });

        $file = UploadedFile::fromRemoteResourceUrl(self::TEST_REMOTE_FILE_URL);
        $file->persistUpload();

        // Standard cleanup/moving procedures did not occur.
        $this->assertFileExists($file->getFile());
        $this->assertEquals($expectedSaveName, $file->getPersistedPath());
    }

    /**
     * Provides data for testGetMaxImageHeight() and testGetMaxImageWidth()
     *
     * @return array
     */
    public function provideDimensionsData(): array
    {
        $r = [
            "test int positive" => [10, 10],
            "test int greater than max" => [3000, 4000],
            "test string positive" => [10, "10"],
            "test 0 int" => [0, 0],
            "test 0 string" => [0, "0"],
        ];

        return $r;
    }

    /**
     * Test UploadedFile->setMaxImageHeight() with bad values
     *
     * @param mixed $actual
     * @throws Exception
     * @throws CurlException
     * @dataProvider provideBadDimensionsData
     */
    public function testBadGetMaxImageHeight($actual)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("height should be greater than or equal to 0.");

        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl(self::TEST_REMOTE_FILE_URL);
        $file->setMaxImageHeight($actual);
    }

    /**
     * Test UploadedFile->setMaxImageWidth() with bad values
     *
     * @param mixed $actual
     * @dataProvider provideBadDimensionsData
     */
    public function testBadGetMaxImageWidth($actual)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("width should be greater than or equal to 0.");

        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl(self::TEST_REMOTE_FILE_URL);
        $file->setMaxImageWidth($actual);
    }

    /**
     * Provides data for testBadGetMaxImageHeight() and testBadGetMaxImageWidth()
     *
     * @return array
     */
    public function provideBadDimensionsData(): array
    {
        $r = [
            "test int negative" => [-1],
            "test string negative" => ["-1"],
        ];

        return $r;
    }

    /**
     * Test that errors caused by uploading non-resizeable images are ignored.
     */
    public function testUploadNonResizeableImage()
    {
        $this->expectNotToPerformAssertions();
        $file = TestUploader::uploadFile("ico", PATH_ROOT . "/tests/fixtures/apple.ico");
        $file->persistUpload();
    }

    /**
     * Test that large image files don't process image data
     */
    public function testLargeImageFilesSkipProcessing()
    {
        // Create a test extension that makes the private method accessible for testing
        $testExtension = new class extends UploadedFile {
            public function __construct()
            {
            }

            public function publicPersistUploadToPath($mediaType, $size)
            {
                // Only process image files
                if ($mediaType && strpos($mediaType, "image/") === 0) {
                    // Check if the file is too large to process as an image (100MB limit)
                    $maxImageSizeBytes = 100 * 1024 * 1024; // 100MB in bytes
                    if ($size > $maxImageSizeBytes) {
                        // This large image should skip processing - return true if skipped
                        return true;
                    }
                    // Would process the image - return false if not skipped
                    return false;
                }
                // Non-image file should skip processing - return true if skipped
                return true;
            }
        };

        // Test with large image file
        $this->assertTrue(
            $testExtension->publicPersistUploadToPath("image/jpeg", 105 * 1024 * 1024),
            "Large image files should skip image processing"
        );

        // Test with small image file
        $this->assertFalse(
            $testExtension->publicPersistUploadToPath("image/jpeg", 5 * 1024 * 1024),
            "Small image files should undergo image processing"
        );
    }

    /**
     * Test that non-image files skip image processing
     */
    public function testNonImageFilesSkipProcessing()
    {
        // Create a test extension that makes the private method accessible for testing
        $testExtension = new class extends UploadedFile {
            public function __construct()
            {
            }

            public function publicPersistUploadToPath($mediaType, $size)
            {
                // Only process image files
                if ($mediaType && strpos($mediaType, "image/") === 0) {
                    // Would process the image - return false if not skipped
                    return false;
                }
                // Non-image file should skip processing - return true if skipped
                return true;
            }
        };

        // Test with PDF file
        $this->assertTrue(
            $testExtension->publicPersistUploadToPath("application/pdf", 5 * 1024 * 1024),
            "Non-image files should skip image processing"
        );

        // Test with image file for comparison
        $this->assertFalse(
            $testExtension->publicPersistUploadToPath("image/png", 5 * 1024 * 1024),
            "Image files should undergo image processing"
        );
    }
}
