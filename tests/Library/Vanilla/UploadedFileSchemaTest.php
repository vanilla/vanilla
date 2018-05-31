<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use Gdn_Upload;
use Vanilla\UploadedFile;
use Vanilla\UploadedFileSchema;
use VanillaTests\BootstrapTrait;

/**
 * Tests for the **UploadedFileSchema** class.
 */
class UploadedFileSchemaTest extends TestCase {
    use BootstrapTrait;

    /**
     * Test an upload possessing an invalid file extension.
     *
     * @throws \Garden\Schema\ValidationException
     * @expectedException \Garden\Schema\ValidationException
     */
    public function testBadExtension() {
        $schema = new UploadedFileSchema();
        $schema->setAllowedExtensions(['jpg']);

        $schema->validate(new UploadedFile(
            new Gdn_Upload(),
            '/tmp/php123',
            40,
            UPLOAD_ERR_OK,
            'image.gif',
            'image/gif'
        ));
        $this->fail('Unable to detect invalid upload extension.');
    }

    /**
     * Test an upload that exceeds the file size restrictions.
     *
     * @throws \Garden\Schema\ValidationException
     * @expectedException \Garden\Schema\ValidationException
     */
    public function testBadSize() {
        $schema = new UploadedFileSchema();
        $schema->setMaxSize(100);

        $schema->validate(new UploadedFile(
            new Gdn_Upload(),
            '/tmp/php123',
            200,
            UPLOAD_ERR_OK,
            'image.jpg',
            'image/jpeg'
        ));
        $this->fail('Unable to detect large file.');
    }

    /**
     * Test an upload possessing no extension.
     *
     * @throws \Garden\Schema\ValidationException
     * @expectedException \Garden\Schema\ValidationException
     */
    public function testNoExtension() {
        $schema = new UploadedFileSchema();

        $schema->validate(new UploadedFile(
            new Gdn_Upload(),
            '/tmp/php123',
            40,
            UPLOAD_ERR_OK,
            'image',
            'image/gif'
        ));
        $this->fail('Unable to detect upload with no extension to validate.');
    }

    /**
     * Test an upload possessing a valid file extension.
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function testGoodExtension() {
        $schema = new UploadedFileSchema();
        $schema->setAllowedExtensions(['jpg']);

        $schema->validate(new UploadedFile(
            new Gdn_Upload(),
            '/tmp/php123',
            80,
            UPLOAD_ERR_OK,
            'image.JPG',
            'image/jpeg'
        ));
        $this->assertTrue(true);
    }

    /**
     * Test an upload that falls within the allowed file size restrictions.
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function testGoodSize() {
        $schema = new UploadedFileSchema();
        $schema->setMaxSize(100);

        $schema->validate(new UploadedFile(
            new Gdn_Upload(),
            '/tmp/php123',
            80,
            UPLOAD_ERR_OK,
            'image.jpg',
            'image/jpeg'
        ));
        $this->assertTrue(true);
    }
}
