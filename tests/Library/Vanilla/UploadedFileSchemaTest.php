<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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

    /**
     * Assert that an uploaded file has the correct mime type..
     *
     * @param string $file The name of the file in the fixtures/uploads folder.
     * @param string $mime The mime type that the browser uploaded with the file..
     * @param bool $expected Whether or not the upload should be valid.
     * @param string[] An optional array of allowed extensions.
     */
    protected function assertUploadedFileMimeType(string $file, string $mime, bool $expected, array $allowedExtensions = []) {
        $file = $this->createUploadFile($file, $mime);
        $schema = new UploadedFileSchema();
        if (!empty($allowedExtensions)) {
            $schema->setAllowedExtensions($allowedExtensions);
        }

        $actual = $schema->isValid($file);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test a file with a bad content type.
     */
    public function testContentTypeBad() {
        $this->assertUploadedFileMimeType('html.fla', 'text/plain', false);
    }

    /**
     * Test a file with a good content type.
     */
    public function testMimeTypeGood() {
        $this->assertUploadedFileMimeType('text.txt', 'text/plain', true);
    }

    public function testUnknownGoodFileExtension() {
        $this->assertUploadedFileMimeType('test.confz0', 'text/plain', true, ['confz0']);
    }

    public function testNonexistantFile() {
        $this->assertUploadedFileMimeType('dont-create-me.text', 'text/plain', false);
    }

    /**
     * Undocumented function
     *
     * @param [type] $file
     * @param [type] $mime
     */
    private function createUploadFile($file, $mime): UploadedFile {
        return new UploadedFile(
            new Gdn_Upload(),
            PATH_FIXTURES . "/uploads/$file",
            80,
            UPLOAD_ERR_OK,
            $file,
            $mime
        );
    }
}
