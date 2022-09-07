<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Models\ContentGroupModel;
use VanillaTests\SiteTestCase;
use VanillaTests\ExpectExceptionTrait;
use Vanilla\Exception\Database\NoResultsException;

class ContentGroupModelTest extends SiteTestCase
{
    use ExpectExceptionTrait;

    /** @var ContentGroupModel */
    private $contentGroupModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->contentGroupModel = $this->container()->get(ContentGroupModel::class);
    }

    /**
     * Test we are getting default record types
     *
     * @return void
     */
    public function testGetAllRecordTypes()
    {
        $defaults = ["category", "discussion"];
        $recordTypes = $this->contentGroupModel->getAllRecordTypes();
        $this->assertEqualsCanonicalizing($defaults, $recordTypes);
    }

    /**
     * Test ContentGroupModel::SaveContentGroup takes a content group record and save information
     *
     * @return void
     */
    public function testSaveContentGroup()
    {
        $contentGroupRecord = ["name" => "Test Content Group Record"];
        $this->runWithExpectedException(\Gdn_UserException::class, function () use ($contentGroupRecord) {
            $this->contentGroupModel->saveContentGroup($contentGroupRecord);
        });
        $contentGroupRecord["records"] = [
            [
                "recordID" => "1",
                "recordType" => "discussion",
            ],
        ];

        $contentGroupID = $this->contentGroupModel->saveContentGroup($contentGroupRecord);
        $this->assertisInt($contentGroupID);

        return $contentGroupID;
    }

    /**
     * @param int $id
     * @return void
     * @depends testSaveContentGroup
     */
    public function testDeleteContentGroup(int $id)
    {
        $this->contentGroupModel->deleteContentGroup($id);
        $this->expectExceptionMessage("No rows matched the provided criteria.");
        $this->expectException(NoResultsException::class);
        $this->contentGroupModel->getContentGroupRecordByID($id);
    }
}
