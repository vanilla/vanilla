<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv0\Controllers;

use VanillaTests\APIv0\BaseTest;

/**
 * Class ModerationControllerTest: tests confirmdiscussionmoves action
 */
class ModerationControllerTest extends BaseTest {
    const FIELDS_CHECK_LIST = [
        'CountAllDiscussions',
        'CountAllComments',
        'CountCategories',
        'CountDiscussions',
        'CountComments',
        'DateInserted',
        'DateUpdated',
        'LastDateInserted',
        'LastDiscussionID',
        'LastCommentID'
    ];
    /**
     * @var array Array of categories holding initial and continually updated valid values for tests
     */
    protected static $categories = [];
    /**
     * @var array Array of discussions holding initial and continually updated valid values for tests
     */
    protected static $discussions = [];

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::$api->saveToConfig([
            'Garden.Registration.Method' => 'Basic',
            'Garden.Registration.ConfirmEmail' => false,
            'Garden.Registration.SkipCaptcha' => true,
            'Cache.Enabled' => false,
        ]);

        $system = self::$api->querySystemUser(true);
        self::$api->setUser($system);
    }

    /**
     * Helper function updates valid data array for tests
     *
     * @param string $catKey
     * @param string $fieldToUpdate
     * @param mixed $newValue
     * @param bool $recursively
     */
    protected static function updateValidValues(string $catKey, string $fieldToUpdate, $newValue, bool $recursively = true) {
        do {
            $continue = false;
            switch ($newValue) {
                case '++':
                    self::$categories[$catKey][$fieldToUpdate]++;
                    break;
                case '--':
                    self::$categories[$catKey][$fieldToUpdate]--;
                    break;
                default:
                    self::$categories[$catKey][$fieldToUpdate] = $newValue;
            }

            if ($recursively) {
                if (($pos = strrpos($catKey, '_')) > 0) {
                    $continue = true;
                    $catKey = substr($catKey, 0, $pos);
                }
            }
        } while ($continue);
    }

    /**
     * Update valid data after discussion being moved from one category to another
     *
     * @param array $discussion
     * @param string $srcCategoryKey
     * @param string $destCategoryKey
     * @param bool $updateRecent
     * @param array $srcDiscussionToUpdate
     */
    protected static function updateValidValuesOnMoveDiscussion(
        array $discussion,
        string $srcCategoryKey,
        string $destCategoryKey,
        bool $updateRecent = true,
        array $srcDiscussionToUpdate = []
    ) {
        self::$discussions[$discussion['discussionKey']]['CategoryID'] = self::$categories[$destCategoryKey]['CategoryID'];
        // Right now CountDiscussions field is not updated at all  - which is wrong
        // @todo We need to uncomment next 2 lines when bug is fixed
        // self::updateValidValues($destCategoryKey,'CountDiscussions','++');
        // self::updateValidValues($srcCategoryKey,'CountDiscussions','--');
        self::updateValidValues($destCategoryKey, 'CountAllDiscussions', '++');
        self::updateValidValues($srcCategoryKey, 'CountAllDiscussions', '--');
        if (!empty(self::$discussions[$discussion['discussionKey']]['LastCommentID'] ?? null)) {
            self::updateValidValues($destCategoryKey, 'CountAllComments', '++');
            self::updateValidValues($srcCategoryKey, 'CountAllComments', '--');
            //self::updateValidValues($srcCategoryKey, 'CountComments', '--', false);
            if ($discussion['LastCommentID'] > self::$categories[$destCategoryKey]['LastCommentID']) {
                self::updateValidValues($destCategoryKey, 'LastCommentID', $discussion['LastCommentID']);
            }
            // Right now LastCommentIDis not updated against source Parent categories - which is wrong
            // @todo We need to switch to recursive mode when bug is fixed
            // @todo until then lets update in non-recursive mode to reproduce current data flow
            self::updateValidValues($srcCategoryKey, 'LastCommentID', ($srcDiscussionToUpdate['LastCommentID'] ?? null), false);
        }

        if ($updateRecent) {
            if (($discussion['DateLastComment'] ?? '') > self::$categories[$destCategoryKey]['LastDateInserted']) {
                self::updateValidValues($destCategoryKey, 'LastDateInserted', $discussion['DateLastComment']);
                self::updateValidValues($destCategoryKey, 'LastDiscussionID', $discussion['DiscussionID']);
            } elseif ($discussion['DateInserted'] > self::$categories[$destCategoryKey]['LastDateInserted']) {
                self::updateValidValues($destCategoryKey, 'LastDateInserted', $discussion['DateInserted']);
                self::updateValidValues($destCategoryKey, 'LastDiscussionID', $discussion['DiscussionID']);
            } else {
                self::updateValidValues($destCategoryKey, 'LastDateInserted', self::$categories[$destCategoryKey]['LastDateInserted']);
                self::updateValidValues($destCategoryKey, 'LastDiscussionID', self::$categories[$destCategoryKey]['LastDiscussionID']);
            }

            // Right now LastDateInserted and LastDiscussionID fields are not updated against source Category - which is wrong
            // @todo We need to uncomment next 2 lines when bug is fixed
            //self::updateValidValues($srcCategoryKey , 'LastDateInserted', ($srcDiscussion['DateInserted'] ?? null));
            //self::updateValidValues($srcCategoryKey , 'LastDiscussionID', ($srcDiscussion['DiscussionID'] ?? null));
            // @todo until then lets update in non-recursive mode to reproduce current data flow
            self::updateValidValues($srcCategoryKey, 'LastDateInserted', ($srcDiscussionToUpdate['DateInserted'] ?? null), false);
            self::updateValidValues($srcCategoryKey, 'LastDiscussionID', ($srcDiscussionToUpdate['DiscussionID'] ?? null), false);
        }
    }

    /**
     * Add new discussion to test database.
     *
     * @param array $discussion
     * @param string $catKey
     * @return mixed
     */
    protected static function addDiscussion(array $discussion, string $catKey) {
        $r = self::$api->post(
            '/post/discussion.json',
            $discussion
        );
        if ($r->getStatusCode() != 200) {
            throwException('Failed to create new discussion: ' . json_encode($discussion));
        }
        $body = $r->getBody();
        if (!empty($catKey)) {
            self::updateValidValues($catKey, 'CountDiscussions', '++', false);
            self::updateValidValues($catKey, 'CountAllDiscussions', '++');
            self::updateValidValues($catKey, 'LastDateInserted', $body['Discussion']['DateInserted']);
            self::updateValidValues($catKey, 'LastDiscussionID', $body['Discussion']['DiscussionID']);
            self::updateValidValues($catKey, 'LastCommentID', null);
        }

        return $body['Discussion'];
    }

    /**
     * Execute move discussion api call
     *
     * @param array $discussion
     * @param string $srcCatKey
     * @param string $destCatKey
     * @param array $srcDiscussion
     */
    protected function moveDiscussion(array $discussion, string $srcCatKey, string $destCatKey, array $srcDiscussion = []) {
        $r = self::$api->post(
            '/moderation/confirmdiscussionmoves.json?discussionid='.$discussion['DiscussionID'],
            [
                'Move' => 'Move',
                'CategoryID' => self::$categories[$destCatKey]['CategoryID']
            ]
        );

        $this->updateValidValuesOnMoveDiscussion($discussion, $srcCatKey, $destCatKey, true, $srcDiscussion);
    }

    /**
     * Add new category to test database.
     *
     * @param array $category
     * @param string $catKey
     * @return mixed
     */
    protected static function addCategory(array $category, string $catKey = '') {
        if (!empty($catKey)) {
            if (($pos = strrpos($catKey, '_')) > 0) {
                $catKey = substr($catKey, 0, $pos);
                if (!($parentCat = (self::$categories[$catKey] ?? false))) {
                    self::$categories[$catKey] = $parentCat = self::addCategory(['Name' => ' Test cat '.$catKey,
                        'UrlCode' => 'test-cat-'.$catKey,
                        'DisplayAs' => 'Discussions'], $catKey);
                }
                $category['ParentCategoryID'] = $parentCat['CategoryID'];
            }
        }

        $r = self::$api->post(
            '/vanilla/settings/addcategory.json',
            $category
        );
        if ($r->getStatusCode() != 200) {
            throwException('Failed to create new category: ' . json_encode($category));
        }
        $body = $r->getBody();
        return $body['Category'];
    }

    /**
     * Add new comment to test database.
     *
     * @param array $comment
     * @param string $discussionKey
     * @param string $catKey
     * @return mixed
     */
    protected static function addComment(array $comment, string $discussionKey, string $catKey) {
        $r = self::$api->post(
            '/post/comment.json',
            $comment
        );
        if ($r->getStatusCode() != 200) {
            throwException('Failed to add new comment: ' . json_encode($comment));
        }
        $body = $r->getBody();
        $comment = $body['Comment'];
        if (!empty($catKey)) {
            self::$discussions[$discussionKey]['LastCommentID'] = $comment['CommentID'];
            self::$discussions[$discussionKey]['DateLastComment'] = $comment['DateInserted'];
            if (empty(self::$categories[$catKey]['LastCommentID'] ?? '')) {
                self::updateValidValues($catKey, 'CountComments', 1, false);
            } else {
                self::updateValidValues($catKey, 'CountComments', '++', false);
            }
            self::updateValidValues($catKey, 'CountAllComments', '++');
            self::updateValidValues($catKey, 'LastDateInserted', $comment['DateInserted']);
            self::updateValidValues($catKey, 'LastDiscussionID', $comment['DiscussionID']);
            self::updateValidValues($catKey, 'LastCommentID', $comment['CommentID']);
        }
        return $comment;
    }

    /**
     * Returns actual category from app (from test database).
     *
     * @param int $categoryId
     * @return mixed
     */
    protected static function getCategory(int $categoryId) {
        $r = self::$api->get(
            '/vanilla/settings/getcategory.json',
            ['CategoryID'=>$categoryId]
        );
        if ($r->getStatusCode() != 200) {
            throwException('Failed to get category: ' . $categoryId);
        }
        $body = $r->getBody();
        return $body['Category'];
    }

    /**
     * Execute asserts to compare prepared valid data
     * vs actual data getting from app/test database through APIv0
     */
    protected function recheckCategories() {
        foreach (self::$categories as $catKey => $category) {
            $cat = $this->getCategory($category['CategoryID']);
            foreach (self::FIELDS_CHECK_LIST as $field) {
                $this->assertEquals($category[$field], $cat[$field], $field.' failed on  '.$catKey);
            }
        }
    }

    /**
     * Use case #1:
     * Src cat Lvl1-6 has 1 discussion
     * Dest cat Lvl1-6 has 0 discussions
     *
     * @large
     *
     */
    public function testMoveNewDiscussionsEmptyCategories() {
        $this->createAndMove('cat1', 'cat2', 'd1_case1');
        $this->createAndMove('cat1_1_1', 'cat2_1_1', 'd1_case2');
        $this->createAndMove('cat1_1_1_1', 'cat2_1_1_1', 'd1_case3');
        $this->createAndMove('cat1_1_1_1_1', 'cat2_1_1_1_1', 'd1_case4');
        $this->createAndMove('cat1_1_1_1_1_1', 'cat2_1_1_1_1_1', 'd1_case5');
        $this->createAndMove('cat1_1_1_1_1_1_1', 'cat2_1_1_1_1_1_1', 'd1_case6');
        $this->recheckCategories();
    }

    /**
     * Use case #2:
     * Src cat Lvl1-6 has 1 discussion
     * Dest cat cat3_1_1 has 0-1-2-3 discussions
     *
     * @large
     *
     * @depends testMoveNewDiscussionsEmptyCategories
     */
    public function testMoveExistingDiscussionToEmptyCategory() {
        $destCatKey = 'cat3_1_1';
        self::$categories[$destCatKey] = $destCategory = self::addCategory([
            'Name' => 'Test cat '.$destCatKey,
            'UrlCode' => 'test-cat-'.$destCatKey,
            'DisplayAs' => 'Discussions'], $destCatKey);
        //We need to test both order from latest to older and backward from older to most recent
        //reverse order brings more changes on data on each call
        $this->moveDiscussion(self::$discussions['d1_case4'], 'cat2_1_1_1_1', $destCatKey, self::$discussions['d1_case6']);
        $this->moveDiscussion(self::$discussions['d1_case3'], 'cat2_1_1_1', $destCatKey, self::$discussions['d1_case6']);
        $this->moveDiscussion(self::$discussions['d1_case2'], 'cat2_1_1', $destCatKey, self::$discussions['d1_case6']);
        $this->moveDiscussion(self::$discussions['d1_case1'], 'cat2', $destCatKey, self::$discussions['d1_case6']);
        $this->recheckCategories();
    }

    /**
     * Use case #3:
     * Move existing discussion to non empty category
     * where destination category already has LastDiscussionID fresher than we move in.
     *
     * @large
     *
     * @depends testMoveNewDiscussionsEmptyCategories
     * @depends testMoveExistingDiscussionToEmptyCategory
     */
    public function testMoveExistingDiscussionToNotEmptyCategory() {
        $destCatKey = 'cat2_1';
        $this->moveDiscussion(self::$discussions['d1_case1'], 'cat3_1_1', $destCatKey, self::$discussions['d1_case4']);
        $this->moveDiscussion(self::$discussions['d1_case2'], 'cat3_1_1', $destCatKey, self::$discussions['d1_case4']);
        $this->recheckCategories();
    }

    /**
     * Use case #4:
     * Move most recent discussion from one category to another
     *
     * @large
     *
     * @depends testMoveNewDiscussionsEmptyCategories
     * @depends testMoveExistingDiscussionToEmptyCategory
     * @depends testMoveExistingDiscussionToNotEmptyCategory
     */
    public function testMoveMostRecentDiscussionToNotEmptyCategory() {
        $this->moveDiscussion(self::$discussions['d1_case6'], 'cat2_1_1_1_1_1_1', 'cat2_1_1');
        $this->moveDiscussion(self::$discussions['d1_case5'], 'cat2_1_1_1_1_1', 'cat3_1_1');
        $this->moveDiscussion(self::$discussions['d1_case5'], 'cat3_1_1', 'cat2_1', self::$discussions['d1_case4']);
        $this->recheckCategories();
    }

    /**
     * Use case #5:
     * New comment add to existing discussion and move that discussion after
     *
     * @large
     *
     * @depends testMoveNewDiscussionsEmptyCategories
     * @depends testMoveExistingDiscussionToEmptyCategory
     * @depends testMoveExistingDiscussionToNotEmptyCategory
     * @depends testMoveMostRecentDiscussionToNotEmptyCategory
     */
    public function testAddCommentMoveDiscussion() {
        $comment = $this->addComment([
            'DiscussionID' => self::$discussions['d1_case1']['DiscussionID'],
            'Body' => 'Moderation controller test.',
            'Format' => 'Text'
        ], 'd1_case1', 'cat2_1');
        $this->recheckCategories();
        $this->moveDiscussion(self::$discussions['d1_case1'], 'cat2_1', 'cat3_1_1', self::$discussions['d1_case6']);
        $this->recheckCategories();
    }

    /**
     * Use case #6:
     * Create new comment to existing discussion and move it to some destCategory
     * After that move we expect it to have most recent fields updated
     * Then move some previous discussion into the same destCategory
     * We expect detCategory recent field to not be updated
     *
     * @large
     *
     * @depends testMoveNewDiscussionsEmptyCategories
     * @depends testMoveExistingDiscussionToEmptyCategory
     * @depends testMoveExistingDiscussionToNotEmptyCategory
     * @depends testMoveMostRecentDiscussionToNotEmptyCategory
     * @depends testAddCommentMoveDiscussion
     */
    public function testCommentDestinationLastCommentFresher() {
        //Create new comment to Discussion d1_case5
        $comment = $this->addComment([
            'DiscussionID' => self::$discussions['d1_case5']['DiscussionID'],
            'Body' => 'Moderation controller test.',
            "Format" => "Text",
        ], 'd1_case5', 'cat2_1');
        // Move discussion d1_case5 to cat1_1_1
        $this->moveDiscussion(self::$discussions['d1_case5'], 'cat2_1', 'cat1_1_1', self::$discussions['d1_case6']);
        // Now cat1_1_1 has most recent comment in DB
        //Lets move something older in
        $this->moveDiscussion(self::$discussions['d1_case1'], 'cat3_1_1', 'cat1_1_1', self::$discussions['d1_case4']);
        // Check if everything is updated correctly
        $this->recheckCategories();
    }

    /**
     * Helper function:
     * automatically creates source and destination categories with its parents recursively
     * create discussion under source category and move it to destination
     *
     * @param string $srcCatKey
     * @param string $destCatKey
     * @param string $discussionKey
     */
    protected function createAndMove(string $srcCatKey, string $destCatKey, string $discussionKey) {
        if (!($srcCategory = (self::$categories[$srcCatKey] ?? false))) {
            self::$categories[$srcCatKey] = $srcCategory = self::addCategory([
                'Name' => 'Test cat '.$srcCatKey,
                'UrlCode' => 'test-cat-'.$srcCatKey,
                'DisplayAs' => 'Discussions'], $srcCatKey);
        }
        $discussion = self::$discussions[$discussionKey] = self::addDiscussion([
            'Name' => 'Discussion 1 of '.$srcCatKey,
            'Body' => 'Test '.$srcCatKey.' '.$destCatKey,
            'CategoryID' => $srcCategory['CategoryID'],
            'Format' => 'Text'
        ], $srcCatKey);
        $discussion['discussionKey'] = self::$discussions[$discussionKey]['discussionKey'] = $discussionKey;
        if (!($destCategory = (self::$categories[$destCatKey] ?? false))) {
            self::$categories[$destCatKey] = $destCategory = self::addCategory([
                'Name' => ' Test cat ' . $destCatKey,
                'UrlCode' => 'test-cat-' . $destCatKey,
                'DisplayAs' => 'Discussions'
            ], $destCatKey);
        }
        $this->moveDiscussion($discussion, $srcCatKey, $destCatKey);
    }
}
