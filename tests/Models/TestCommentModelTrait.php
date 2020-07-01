<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;

/**
 * Use this trait to get some comment model test boilerplate.
 */
trait TestCommentModelTrait {
    /**
     * @var \CommentModel
     */
    protected $commentModel;

    /**
     * Instantiate the comment model fixture.
     */
    public function setUpTestCommentModel() {
        $this->commentModel = $this->container()->get(\CommentModel::class);
    }

    /**
     * Create a test record.
     *
     * @param array $override
     *
     * @return array
     */
    public function newComment(array $override): array {
        static $i = 1;

        $r = $override + [
                'DiscussionID' => 1,
                'Body' => "Foo $i.",
                'Format' => 'Text',
                'DateInserted' => TestDate::mySqlDate(),
            ];

        return $r;
    }

    /**
     * Insert test records and return them.
     *
     * @param int $count
     * @param array $overrides An array of row overrides.
     * @return array
     */
    protected function insertComments(int $count, array $overrides = []): array {
        $ids = [];
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $id = $this->commentModel->save($this->newComment($overrides));
            TestCase::assertNotFalse($id);
            $row = $this->commentModel->getID($id);
            \CategoryModel::instance()->incrementLastComment($row);
            $ids[] = $id;
            $rows[] = $row;
        }
        TestCase::assertCount($count, $rows, "Not enough test comments were inserted.");
        return $rows;
    }
}
