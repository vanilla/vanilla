<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/discussions endpoints.
 */
class DiscussionsTest extends AbstractResourceTest {

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/discussions';
        $this->record += ['categoryID' => 1, 'name' => __CLASS__];

        $this->patchFields = ['body', 'categoryID', 'closed', 'format', 'name', 'pinLocation', 'pinned', 'sink'];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row) {
        $row = parent::modifyRow($row);

        $row['closed'] = !$row['closed'];
        $row['pinned'] = !$row['pinned'];
        if ($row['pinned']) {
            $row['pinLocation'] = $row['pinLocation'] == 'category' ? 'recent' : 'category';
        } else {
            $row['pinLocation'] = null;
        }
        $row['sink'] = !$row['sink'];

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function providePutFields() {
        $fields = [
            'bookmark' => ['bookmark', true, 'bookmarked'],
        ];
        return $fields;
    }

    /**
     * Verify a bookmarked discussion shows up under /discussions/bookmarked.
     */
    public function testBookmarked() {
        $row = $this->testPost();
        $rowID = $row['discussionID'];
        $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/bookmark", ['bookmarked' => 1]);
        $bookmarked = $this->api()->get("{$this->baseUrl}/bookmarked")->getBody();
        $discussionIDs = array_column($bookmarked, 'discussionID');
        $this->assertContains($rowID, $discussionIDs);
    }

    /**
     * Test PATCH /discussions/<id> with a a single field update.
     *
     * @param string $field The name of the field to patch.
     * @dataProvider providePatchFields
     */
    public function testPatchSparse($field) {
        // pinLocation doesn't do anything on its own, it requires pinned. It's not a good candidate for a single-field sparse PATCH.
        if ($field == 'pinLocation') {
            $this->markTestSkipped('pinLocation cannot be used alone in PATCH.');
        }

        parent::testPatchSparse($field);
    }
}
