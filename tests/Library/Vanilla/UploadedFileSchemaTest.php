<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Garden\Schema\ValidationException;
use PHPUnit\Framework\TestCase;
use Gdn_Upload;
use Vanilla\Http\InternalRequest;
use Vanilla\UploadedFile;
use Vanilla\UploadedFileSchema;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\Request;

/**
 * Tests for the **UploadedFileSchema** class.
 */
class UploadedFileSchemaTest extends TestCase
{
    use BootstrapTrait;

    /**
     * Test an upload possessing an invalid file extension.
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function testBadExtension()
    {
        $this->expectException(ValidationException::class);

        $schema = new UploadedFileSchema(["validateContentTypes" => true]);
        $schema->setAllowedExtensions(["jpg"]);

        $schema->validate(
            new UploadedFile(
                new Gdn_Upload(),
                PATH_FIXTURES . "/uploads/valid/doc.docx",
                40,
                UPLOAD_ERR_OK,
                "image.gif",
                "image/gif"
            )
        );
        $this->fail("Unable to detect invalid upload extension.");
    }

    /**
     * Test an upload that exceeds the file size restrictions.
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function testBadSize()
    {
        $this->expectException(ValidationException::class);
        $this->getExpectedExceptionMessageRegExp("`exceeds the maximum file size`");

        $schema = new UploadedFileSchema();
        $schema->setMaxSize(100);

        $schema->validate(
            new UploadedFile(
                new Gdn_Upload(),
                PATH_FIXTURES . "/uploads/valid/doc.docx",
                200,
                UPLOAD_ERR_OK,
                "image.jpg",
                "image/jpeg"
            )
        );
        $this->fail("Unable to detect large file.");
    }

    /**
     * Test an upload possessing no extension.
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function testNoExtension()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("does not contain a file extension");

        $schema = new UploadedFileSchema();

        $schema->validate(
            new UploadedFile(
                new Gdn_Upload(),
                PATH_FIXTURES . "/uploads/valid/doc.docx",
                40,
                UPLOAD_ERR_OK,
                "image",
                "image/gif"
            )
        );
        $this->fail("Unable to detect upload with no extension to validate.");
    }

    /**
     * Test an upload possessing a valid file extension.
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function testGoodExtension()
    {
        $schema = new UploadedFileSchema();
        $schema->setAllowedExtensions(["jpg"]);

        $schema->validate(
            new UploadedFile(
                new Gdn_Upload(),
                PATH_FIXTURES . "/apple.jpg",
                80,
                UPLOAD_ERR_OK,
                "image.JPG",
                "image/jpeg"
            )
        );
        $this->assertTrue(true);
    }

    /**
     * Real uploads have a different filename than the actual file.
     */
    public function testDifferentClientFilename()
    {
        $schema = new UploadedFileSchema();
        $schema->setAllowedExtensions(["jpg"]);

        $schema->validate(
            new UploadedFile(
                new Gdn_Upload(),
                PATH_FIXTURES . "/uploads/apple",
                80,
                UPLOAD_ERR_OK,
                "image.JPG",
                "image/jpeg"
            )
        );
        $this->assertTrue(true);
    }

    /**
     * Test an upload that falls within the allowed file size restrictions.
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function testGoodSize()
    {
        $schema = new UploadedFileSchema();
        $schema->setMaxSize(100);

        $schema->validate(
            new UploadedFile(
                new Gdn_Upload(),
                PATH_FIXTURES . "/apple.jpg",
                80,
                UPLOAD_ERR_OK,
                "image.jpg",
                "image/jpeg"
            )
        );
        $this->assertTrue(true);
    }

    /**
     * Assert that an uploaded file has the correct mime type..
     *
     * @param string $file The name of the file in the fixtures/uploads folder.
     * @param string $mime The mime type that the browser uploaded with the file..
     * @param bool|null $expected Whether or not the upload should be valid.
     * @param array $options Options for the `UploadFileSchema`.
     */
    protected function assertUploadedFileMimeType(
        string $file,
        string $mime,
        bool $expected = null,
        array $options = []
    ) {
        $file = $this->createUploadFile($file, $mime);

        $options += [
            UploadedFileSchema::OPTION_VALIDATE_CONTENT_TYPES => true,
            UploadedFileSchema::OPTION_ALLOWED_EXTENSIONS => [
                strtolower(pathinfo($file->getFile(), PATHINFO_EXTENSION)),
            ],
            UploadedFileSchema::OPTION_MAX_SIZE => 80,
            UploadedFileSchema::OPTION_ALLOW_UNKNOWN_TYPES => false,
        ];
        $schema = new UploadedFileSchema($options);
        $schema->setTriggerContentTypeError(false);

        if ($expected === null) {
            $schema->validate($file);
            $this->assertTrue(true);
        } else {
            $actual = $schema->isValid($file);
            $this->assertSame($expected, $actual);
        }
    }

    /**
     * Test a file with a bad content type.
     */
    public function testContentTypeBad()
    {
        $this->assertUploadedFileMimeType("html.fla", "text/plain", false);
    }

    /**
     * Test a file with a good content type.
     */
    public function testContentTypeGood()
    {
        $this->assertUploadedFileMimeType("text.txt", "text/plain", true);
    }

    /**
     * An unknown file extension that is really just plain text should pass.
     */
    public function testUnknownGoodFileExtension()
    {
        $this->assertUploadedFileMimeType("test.confz0", "text/plain", true, [
            UploadedFileSchema::OPTION_ALLOWED_EXTENSIONS => ["confz0"],
        ]);
    }

    /**
     * A non-existent file should fail upload validation.
     */
    public function testNonexistantFile()
    {
        $this->assertUploadedFileMimeType("dont-create-me.txt", "text/plain", false);
    }

    /**
     * A random binary file should be allowed if we allow unknown types.
     */
    public function testRandomFileValid()
    {
        $this->assertUploadedFileMimeType("random.fooxds", "", true, [
            UploadedFileSchema::OPTION_ALLOW_UNKNOWN_TYPES => true,
        ]);
    }

    /**
     * By default a random binary file should be invalid.
     */
    public function testRandomFileInvalid()
    {
        $this->assertUploadedFileMimeType("random.fooxds", "", false);
    }

    /**
     * Assert an uploaded file.
     *
     * @param string $filename The name of the file.
     * @dataProvider provideTestFiles
     */
    public function testValidFile(string $filename)
    {
        $this->assertUploadedFileMimeType("valid/$filename", "", null, [
            UploadedFileSchema::OPTION_ALLOW_NON_STRICT_TYPES => true,
        ]);
    }

    /**
     * Provide test files from the fixtures/uploads folder.
     *
     * @return array Returns a data provider array.
     */
    public function provideTestFiles()
    {
        $files = glob(PATH_FIXTURES . "/uploads/valid/*.*");

        $r = [];
        foreach ($files as $path) {
            $file = basename($path);
            $r[$file] = [$file];
        }

        return $r;
    }

    /**
     * Create an uploaded file for testing.
     *
     * @param string $file The name of the file.
     * @param string $mime The mime type that the "browser" sent.
     * @return UploadedFile Returns a new uploaded file.
     */
    private function createUploadFile(string $file, string $mime): UploadedFile
    {
        return new UploadedFile(new Gdn_Upload(), PATH_FIXTURES . "/uploads/$file", 80, UPLOAD_ERR_OK, $file, $mime);
    }

    /**
     * The `UploadedFileSchema::validateUploadSanity()` method should have an exception if an expected file upload request
     * looks malformed.
     */
    public function testValidateUploadSanityException(): void
    {
        $request = new Request("/", "POST", []);
        $request->setHeader("content-type", "application/json");

        $this->expectExceptionMessage("Make sure the content-type is multipart/form-data.");
        UploadedFileSchema::validateUploadSanity([], "photo", $request);
    }

    /**
     * Test upload sanity happy paths.
     *
     * The `UploadedFileSchema::validateUploadSanity()` should only validate against errors that might cause future
     * validation to become confusing. The `UploadFileSchema` itself is used to further validate other issues.
     */
    public function testValidateUploadSanityHappy(): void
    {
        $request = new Request("/", "POST", []);
        $request->setHeader("content-type", "multipart/form-data");
        $this->expectNotToPerformAssertions();
        UploadedFileSchema::validateUploadSanity(["photo" => "foo"], "photo", $request);
        UploadedFileSchema::validateUploadSanity([], "photo", $request);
    }
}
