<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Models;

use CommentModel;
use VanillaTests\SiteTestTrait;

/**
 * Test {@link CommentModel}.
 */
class CommentModelTest extends \PHPUnit_Framework_TestCase {
    use SiteTestTrait;

    /**
     * Test the lookup method.
     */
    public function testLookup() {
        $model = new CommentModel();
        $fields = [
            'DiscussionID' => 9999,
            'Body' => 'Hello world.',
            'Format' => 'Text'
        ];
        $id = $model->save($fields);
        $this->assertNotFalse($id);

        $result = $model->lookup(['CommentID' => $id] + $fields);
        $this->assertInstanceOf('Gdn_DataSet', $result);
        $this->assertEquals(1, $result->count());

        $row = $result->firstRow(DATASET_TYPE_ARRAY);
        $this->assertEquals($id, $row['CommentID']);
    }
}
