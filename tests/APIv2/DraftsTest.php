<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/drafts endpoints.
 */
class DraftsTest extends AbstractResourceTest {

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/drafts';
        $this->record = [
            'recordType' => 'comment',
            'parentRecordID' => 1,
            'attributes' => [
                'body' => 'Hello world. I am a comment.',
                'format' => 'Markdown'
            ]
        ];

        $this->patchFields = ['parentRecordID', 'attributes'];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row) {
        $row = parent::modifyRow($row);
        $formats = ['BBCode', 'Html', 'Markdown', 'Text', 'TextEx', 'Wysiwyg'];
        shuffle($formats);

        if (array_key_exists('parentRecordID', $row)) {
            $row['parentRecordID']++;
        }
        if (array_key_exists('attributes', $row) && is_array($row['attributes'])) {
            foreach ($row['attributes'] as $key => &$val) {
                if ($key == 'format') {
                    $val = $formats[0];
                } elseif (filter_var($val, FILTER_VALIDATE_BOOLEAN)) {
                    $val = !(bool)$val;
                } elseif (filter_var($val, FILTER_VALIDATE_INT)) {
                    $val++;
                } else {
                    $val = strval($val).microtime();
                }
            }
        }

        return $row;
    }

    /**
     * Verify the ability to create a discussion draft.
     */
    public function testPostDiscussion() {
        $data = [
            'recordType' => 'discussion',
            'parentRecordID' => 1,
            'attributes' => [
                'announce' => 1,
                'body' => 'Hello world.',
                'closed' => 1,
                'format' => 'Markdown',
                'name' => 'Discussion Draft',
                'sink' => 0,
                'tags' => 'interesting,helpful'
            ]
        ];
        parent::testPost($data);
    }
}
