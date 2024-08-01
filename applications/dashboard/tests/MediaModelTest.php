<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard;

use Garden\Web\Exception\NotFoundException;
use MediaModel;
use Vanilla\UploadedFile;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Library\Vanilla\UploadedFileTest;

/**
 * Tests for the media model.
 */
class MediaModelTest extends AbstractAPIv2Test
{
    protected static $addons = ["vanilla", "dashboard"];

    /** @var MediaModel */
    protected $mediaModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->mediaModel = self::container()->get(\MediaModel::class);
    }

    /**
     * Test saving and finding of uploads.
     */
    public function testSaveUpload()
    {
        $file = UploadedFile::fromRemoteResourceUrl(UploadedFileTest::TEST_REMOTE_FILE_URL);
        $insertedMedia = $this->mediaModel->saveUploadedFile($file);

        $mediaByID = $this->mediaModel->findUploadedMediaByID($insertedMedia["mediaID"]);
        $this->assertEquals($insertedMedia, $mediaByID, "Media records can be found by ID.");

        $mediaByUrl = $this->mediaModel->findUploadedMediaByUrl($insertedMedia["url"]);
        $this->assertEquals($insertedMedia, $mediaByUrl, "Media records can be found by URL.");

        $mediaByForeign = $this->mediaModel->findUploadedMediaByForeignUrl($file->getForeignUrl());
        $this->assertEquals($insertedMedia, $mediaByForeign, "Media records can be found by foreign URL.");
    }

    /**
     * Test deleting a file.
     */
    public function testFileDeletion()
    {
        $this->resetTable("Media");
        $file = UploadedFile::fromRemoteResourceUrl(UploadedFileTest::TEST_REMOTE_FILE_URL);
        $insertedMedia = $this->mediaModel->saveUploadedFile($file);
        // Make sure the file do exists
        $media = $this->mediaModel->getID($insertedMedia["mediaID"], DATASET_TYPE_ARRAY);
        $this->assertFileExists(PATH_UPLOADS . "/" . $media["Path"]);

        $this->mediaModel->deleteID($insertedMedia["mediaID"], ["deleteFile" => true]);
        $this->assertFileDoesNotExist(PATH_UPLOADS . "/" . $media["Path"]);

        $this->expectException(NotFoundException::class);
        $this->mediaModel->findUploadedMediaByID($insertedMedia["mediaID"]);
    }
}
