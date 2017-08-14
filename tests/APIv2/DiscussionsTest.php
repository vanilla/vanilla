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

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    public function providePutFields() {
        $fields = [
            'announce' => ['announce', true],
            'bookmark' => ['bookmark', true, 'bookmarked'],
            'close' => ['close', true, 'closed'],
            'sink' => ['sink', true]
        ];
        return $fields;
    }
}
