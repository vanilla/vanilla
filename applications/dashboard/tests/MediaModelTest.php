<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard;

use Vanilla\UploadedFile;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests for the media model.
 */
class MediaModelTest extends AbstractAPIv2Test {

    protected static $addons = ['vanilla', 'dashboard'];

    /**
     * @return \MediaModel
     */
    private function getMediaModel(): \MediaModel {
        return self::container()->get(\MediaModel::class);
    }

    /**
     * Test saving and finding of uploads.
     */
    public function testSaveUpload() {
        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');
        $model = $this->getMediaModel();

        $insertedMedia = $model->saveUploadedFile($file);

        $mediaByID = $model->findUploadedMediaByID($insertedMedia['mediaID']);
        $this->assertEquals($insertedMedia, $mediaByID, 'Media records can be found by ID.');

        $mediaByUrl = $model->findUploadedMediaByUrl($insertedMedia['url']);
        $this->assertEquals($insertedMedia, $mediaByUrl, 'Media records can be found by URL.');

        $mediaByForeign = $model->findUploadedMediaByForeignUrl($file->getForeignUrl());
        $this->assertEquals($insertedMedia, $mediaByForeign, 'Media records can be found by foreign URL.');
    }
}
