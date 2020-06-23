<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;


class DiscussionModelCountsTest extends CountsTest {
    public function testSetupCounts() {
        $this->assertAllCounts();
    }

    public function testDeleteMultipleDiscussions() {
        $toDelete = [];
        $cats = [];

        foreach ($this->discussions as $discussion) {
            if (!in_array($discussion['CategoryID'], $cats)) {
                $cats[] = $discussion['CategoryID'];
                $toDelete[] = $discussion['DiscussionID'];
            }
        }

        $r = $this->discussionModel->deleteID($toDelete);

        foreach ($cats as $catID) {
            $this->assertCategoryCounts($catID);
        }
    }
}
