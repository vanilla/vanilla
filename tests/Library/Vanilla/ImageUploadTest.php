<?php

namespace VanillaTests\Library\Vanilla;

use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\TestUploader;

// Image upload test
class ImageUploadTest extends BootstrapTestCase
{
    /**
     * Test file Mime Type based on the file extension.
     * @return void
     * @throws \Exception
     */
    public function testImageUploadFileMimeType(): void
    {
        $file = TestUploader::uploadFile("Avatar", PATH_ROOT . "/tests/fixtures/apple.jpg");
        $upload = new \Gdn_UploadImage();
        $this->assertFalse($upload::checkMimeType($file->getFile(), "png"));
        $this->assertTrue($upload::checkMimeType($file->getFile(), "jpg"));
    }

    /**
     * Test Validate image upload throws exception on invalid mime type.
     *
     * @return void
     * @throws \Gdn_UserException
     */
    public function testValidateImageUpload()
    {
        TestUploader::uploadFile("Avatar", PATH_ROOT . "/tests/fixtures/testImage.png");
        \Gdn::config()->set("Feature.validateContentTypes.Enabled", true);
        $upload = new \Gdn_UploadImage();
        $this->expectException(\Gdn_UserException::class);
        $this->expectExceptionMessage("MIME type doesn't match the image extension.");
        $upload->validateUpload("Avatar", true);
    }
}
