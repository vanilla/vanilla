<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Garden\EventManager;
use Garden\SafeCurl\Exception\InvalidURLException;
use PHPUnit\Framework\TestCase;
use Vanilla\UploadedFile;
use VanillaTests\BootstrapTestCase;
use VanillaTests\BootstrapTrait;

/**
 * Tests for uploaded files.
 */
class UploadedFileTest extends BootstrapTestCase {
    /**
     * Test that various internal IPs cannot be redirected too.
     *
     * @param string $blacklistedAddress
     *
     * @dataProvider blacklistProvider
     */
    public function testCreateFromRemoteBlacklist(string $blacklistedAddress) {
        $this->expectException(InvalidURLException::class);
        UploadedFile::fromRemoteResourceUrl($blacklistedAddress);
    }

    /**
     * @return array
     */
    public function blacklistProvider(): array {
        return [
            ['0.0.0.0/8'],
            ["file:///etc/passwd"],
            ["gopher://localhost"],
            ["telnet://localhost:25"],
        ];
    }

    /**
     * Test that redirects are followed.
     */
    public function testSavesRemoteUrls() {
        $file = UploadedFile::fromRemoteResourceUrl('http://vanillaforums.com');
        $this->assertEquals('http://vanillaforums.com', $file->getForeignUrl());
        $this->assertEquals('https://vanillaforums.com/en/', $file->getResolvedForeignUrl());

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertTrue(file_exists($file->getFile()));
    }

    /**
     * Test file persistence.
     */
    public function testPersistFile() {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertFileExists($file->getFile());

        // Save the upload.
        $file->persistUpload();
        $this->assertFileNotExists($file->getFile(), 'The original upload is moved and cleaned up.');
        $this->assertFileExists(PATH_UPLOADS.'/'.$file->getPersistedPath(), 'Final upload file is persisted');

        $this->assertStringContainsString('migrated/', $file->getPersistedPath(), 'Persisted remote files should contain "/migrated/"');
        $this->assertStringContainsString($file->getClientFilename(), $file->getPersistedPath(), 'Persisted remote files should the real name.');
    }

    /**
     * Test that we can safely persist files with spaces in their name.
     *
     * @param string $name
     *
     * @dataProvider provideImagesWithSpaces
     */
    public function testPersistFileWithSpaces(string $name) {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl($name);

        // Save the upload.
        $file->persistUpload();
        $this->assertFileExists(PATH_UPLOADS.'/'.$file->getPersistedPath(), 'Final upload file is persisted');
        $this->assertStringContainsString('image-with-spaces.jpg', $file->getPersistedPath());
    }

    /**
     * Test that we can safely persist files with url_encoded characters in their name.
     *
     * @param string $name
     *
     * @dataProvider provideImagesEncodedChars
     */
    public function testPersistFileEncodedChars(string $name) {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl($name);

        // Save the upload.
        $file->persistUpload();
        $this->assertFileExists(PATH_UPLOADS.'/'.$file->getPersistedPath(), 'Final upload file is persisted');
        $this->assertStringContainsString('my-25e5-259c-2596-25e7-2589-2587.png', $file->getPersistedPath());
    }

    /**
     * @return string[][]
     */
    public function provideImagesWithSpaces(): array {
        return [
            'no url encoding' => ['https://us.v-cdn.net/6032207/uploads/770/Image with spaces.jpg'],
            'with url encoding' => ['https://us.v-cdn.net/6032207/uploads/770/Image%20with%20spaces.jpg'],
        ];
    }

    /**
     * @return string[][]
     */
    public function provideImagesEncodedChars(): array {
        return [
            'with url encoding chinese chars' => ['https://us.v-cdn.net/5022541/uploads/320EG16UF3D6/my-%25E5%259C%2596%25E7%2589%2587.png'],
        ];
    }

    /**
     * Test that custom paths can be persisted.
     */
    public function testCustomPersistedPath() {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertFileExists($file->getFile());

        // Save the upload.
        $file->persistUpload(false, 'subdir', 'prefix-%s');
        $this->assertFileExists(PATH_UPLOADS.'/'.$file->getPersistedPath(), 'Final upload file is persisted');
        $this->assertStringMatchesFormat('subdir/%s/prefix-logo.svg', $file->getPersistedPath());
    }

    /**
     * Test copying of a file.
     */
    public function testCopying() {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertFileExists($file->getFile());

        // Save the upload.
        $file->persistUpload(true, 'copied');
        $this->assertFileExists(PATH_UPLOADS.'/'.$file->getPersistedPath(), 'Final upload file is persisted');
        $this->assertFileExists($file->getFile(), 'Original file is not deleted');
    }

    /**
     * Test that an event handler can completely handle the persistance.
     */
    public function testPersistEventHandling() {
        /** @var EventManager $eventManager */
        $eventManager = self::container()->get(EventManager::class);
        $expectedSaveName = 'custom/save/name.result';

        $eventManager->bind('gdn_upload_saveAs', function ($upload, $args) use ($expectedSaveName) {
            $args['Handled'] = true;
            $args['Parsed']['SaveName'] = $expectedSaveName;
        });

        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');
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
    public function provideDimensionsData(): array {
        $r = [
            'test int positive' => [10, 10],
            'test int greater than max' => [3000, 4000],
            'test string positive' => [10, '10'],
            'test 0 int' => [0, 0],
            'test 0 string' => [0, '0'],
        ];

        return $r;
    }

    /**
     * Test UploadedFile->setMaxImageHeight() with bad values
     *
     * @param mixed $actual
     * @dataProvider provideBadDimensionsData
     */
    public function testBadGetMaxImageHeight($actual) {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('height should be greater than or equal to 0.');

        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');
        $file->setMaxImageHeight($actual);
    }

    /**
     * Test UploadedFile->setMaxImageWidth() with bad values
     *
     * @param mixed $actual
     * @dataProvider provideBadDimensionsData
     */
    public function testBadGetMaxImageWidth($actual) {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('width should be greater than or equal to 0.');

        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');
        $file->setMaxImageWidth($actual);
    }

    /**
     * Provides data for testBadGetMaxImageHeight() and testBadGetMaxImageWidth()
     *
     * @return array
     */
    public function provideBadDimensionsData(): array {
        $r = [
            'test int negative' => [-1],
            'test string negative' => ['-1']
        ];

        return $r;
    }
}
