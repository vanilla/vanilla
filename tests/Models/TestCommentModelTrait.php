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
    private $commentModel;

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
    private function insertComments(int $count, array $overrides = []): array {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->commentModel->save($this->newComment($overrides));
        }
        $rows = $this->commentModel->getWhere(['CommentID' => $ids])->resultArray();
        TestCase::assertCount($count, $rows, "Not enough test comments were inserted.");
        return $rows;
    }
}
