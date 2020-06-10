<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

/**
 * Useful methods for testing a discussion model.
 */
trait TestDiscussionModelTrait {
    /**
     * @var \DiscussionModel
     */
    private $model;

    /**
     * Create a test record.
     *
     * @param array $override
     *
     * @return array
     */
    public function createRecord(array $override): array {
        static $i = 1;

        $r = $override + [
                'Name' => "How do I test $i?",
                'CategoryID' => 1,
                'Body' => "Foo $i.",
                'Format' => 'Text',
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
    private function insertRecords(int $count, array $overrides = []): array {
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->model->save($this->createRecord($overrides));
        }
        $rows = $this->model->getWhere(['DiscussionID' => $ids, 'Announce' => 'All'])->resultArray();
        return $rows;
    }
}
