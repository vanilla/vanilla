<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the tag model.
 */
class TagModelTest extends TestCase {

    use SiteTestTrait;
    use ModelTestTrait;

    /** @var \TagModel */
    private $tagModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        $this->setupSiteTestTrait();
        $this->tagModel = self::container()->get(\TagModel::class);
        $this->tagModel->SQL->truncate('Tag');
    }

    /**
     * Test getting tagIDs by their tag name.
     */
    public function testGetTagIDsByName() {
        $tag1 = $this->tagModel->save([
            'Name' => 'Test1',
            'FullName' => 'Test 1 Full',
            'Type' => 'Status',
        ]);

        $tag2 = $this->tagModel->save([
            'Name' => 'Test2',
            'FullName' => 'Test 2 Full',
            'Type' => 'Status',
        ]);

        $this->assertIDsEqual([$tag1, $tag2], $this->tagModel->getTagIDsByName(['Test1', 'Test2']));
    }
}
