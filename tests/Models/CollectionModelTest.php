<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Models\CollectionModel;
use VanillaTests\SiteTestCase;
use VanillaTests\ExpectExceptionTrait;
use Vanilla\Exception\Database\NoResultsException;

class CollectionModelTest extends SiteTestCase
{
    use ExpectExceptionTrait;

    /** @var CollectionModel */
    private $collectionModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->collectionModel = $this->container()->get(CollectionModel::class);
    }

    /**
     * Test we are getting default record types
     *
     * @return void
     */
    public function testGetAllRecordTypes()
    {
        $defaults = ["category", "discussion"];
        $recordTypes = $this->collectionModel->getAllRecordTypes();
        $this->assertEqualsCanonicalizing($defaults, $recordTypes);
    }

    /**
     * Test CollectionModel::SaveCollection takes a collection record and save information
     *
     * @return void
     */
    public function testSaveCollection()
    {
        $collectionRecord = ["name" => "Test Collection"];
        $this->runWithExpectedException(\Gdn_UserException::class, function () use ($collectionRecord) {
            $this->collectionModel->saveCollection($collectionRecord);
        });
        $collectionRecord["records"] = [
            [
                "recordID" => "1",
                "recordType" => "discussion",
            ],
        ];

        $collectionID = $this->collectionModel->saveCollection($collectionRecord);
        $this->assertisInt($collectionID);

        return $collectionID;
    }

    /**
     * @param int $id
     * @return void
     * @depends testSaveCollection
     */
    public function testDeleteCollection(int $id)
    {
        $this->collectionModel->deleteCollection($id);
        $this->expectExceptionMessage("No rows matched the provided criteria.");
        $this->expectException(NoResultsException::class);
        $this->collectionModel->getCollectionRecordByID($id);
    }
}
