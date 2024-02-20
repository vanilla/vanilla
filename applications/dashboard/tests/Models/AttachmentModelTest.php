<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Models;

use Gdn;
use VanillaTests\SiteTestCase;

/**
 * Test the AttachmentModel class.
 */
class AttachmentModelTest extends SiteTestCase
{
    protected \AttachmentModel $attachmentModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->attachmentModel = Gdn::getContainer()->get(\AttachmentModel::class);
    }

    /**
     * Test the rowID() method.
     *
     * @param $row
     * @param $expected
     * @return void
     * @dataProvider provideTestRowIDData
     */
    public function testRowID($row, $expected)
    {
        $this->assertEquals($expected, $this->attachmentModel->rowID($row));
    }

    /**
     * Provide test data for testRowID().
     *
     * @return array
     */
    public function provideTestRowIDData(): array
    {
        $r = [
            [
                [
                    "commentID" => 999,
                ],
                "c-999",
            ],
            [
                [
                    "CommentID" => 999,
                ],
                "c-999",
            ],
            [
                [
                    "discussionID" => 999,
                ],
                "d-999",
            ],
            [
                [
                    "DiscussionID" => 999,
                ],
                "d-999",
            ],
            [
                [
                    "userID" => 999,
                ],
                "u-999",
            ],
            [
                [
                    "UserID" => 999,
                ],
                "u-999",
            ],
        ];

        return $r;
    }

    /**
     * Test the rowID() method with an invalid row.
     *
     * @return void
     * @throws \Gdn_UserException
     */
    public function testRowIDWithInvalidRow(): void
    {
        $row = [
            "foo" => "bar",
        ];

        $this->expectException(\Gdn_UserException::class);
        $this->expectExceptionMessage("Failed to get Type...");
        $this->attachmentModel->rowID($row);
    }

    /**
     * Test the rowID() method with an object.
     *
     * @return void
     */
    public function testRowIDWithObject(): void
    {
        $row = new \stdClass();
        $row->CommentID = 999;

        $this->assertEquals("c-999", $this->attachmentModel->rowID($row));
    }

    /**
     * Test the splitForeignID() method.
     *
     * @param string $id
     * @param array $expected
     * @return void
     * @dataProvider provideTestSplitForeignIDData
     */
    public function testSplitForeignID(string $id, array $expected): void
    {
        $this->assertEquals($expected, $this->attachmentModel->splitForeignID($id));
    }

    /**
     * Provide test data for testSplitForeignID().
     *
     * @return array[]
     */
    public function provideTestSplitForeignIDData(): array
    {
        $r = [
            [
                "c-999",
                [
                    "recordType" => "comment",
                    "recordID" => 999,
                ],
            ],
            [
                "d-999",
                [
                    "recordType" => "discussion",
                    "recordID" => 999,
                ],
            ],
            [
                "u-999",
                [
                    "recordType" => "user",
                    "recordID" => 999,
                ],
            ],
        ];

        return $r;
    }

    /**
     * Test the splitForeignID() method with an invalid ID (no hyphen to split into parts).
     *
     * @return void
     */
    public function testSplitIDWithInvalidID(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid foreign ID: nohyphen");
        $this->attachmentModel->splitForeignID("nohyphen");
    }

    /**
     * Test the splitForeignID() method with an invalid prefix.
     *
     * @return void
     */
    public function testSplitIDWithInvalidPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid foreign ID: i-999");
        $this->attachmentModel->splitForeignID("i-999");
    }
}
