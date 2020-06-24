<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

/**
 * Verify discussion count routines.
 */
class DiscussionModelCountsTest extends CountsTest {

    /**
     * Assert counts for all known records is accurate.
     */
    public function testSetupCounts() {
        $this->assertAllCounts();
    }

    /**
     * Verify counts after multiple discussions across different categories have been deleted.
     */
    public function testDeleteMultipleDiscussions() {
        $toDelete = [];
        $cats = [];

        foreach ($this->discussions as $discussion) {
            if (!in_array($discussion['CategoryID'], $cats)) {
                $cats[] = $discussion['CategoryID'];
                $toDelete[] = $discussion['DiscussionID'];
            }
        }

        $this->discussionModel->deleteID($toDelete);

        foreach ($cats as $catID) {
            $this->assertCategoryCounts($catID);
        }
    }
}
