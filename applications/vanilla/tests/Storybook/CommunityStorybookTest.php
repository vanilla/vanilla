<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Storybook;

use Vanilla\CurrentTimeStamp;
use Vanilla\FeatureFlagHelper;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * HTML generation for the community in foundation.
 */
class CommunityStorybookTest extends StorybookGenerationTestCase {

    use CommunityApiTestTrait;
    use EventSpyTestTrait;

    /** @var string[] */
    public static $addons = ["IndexPhotos"];

    /** @var array */
    private static $commentedDiscussionID;

    /**
     * Test Setup.
     */
    public function testSetup() {
        CurrentTimeStamp::mockTime('Dec 1 2019');
        \Gdn::config()->saveToConfig([
            'Vanilla.Categories.Use' => true,
            'Vanilla.Categories.DoHeadings' => true,
            'Feature.NewQuickLinks.Enabled' => true,
        ]);

        $customCat = $this->createCategory([
            'name' => 'My Custom Category',
            'description' => 'This is a category description',
        ])['categoryID'];
        $this->createDiscussion(['name' => 'Hello Discussion 0', 'score' => 20]);

        $anotherCat = $this->createCategory([
            'name' => 'Another category',
            'description' => 'This is a category description',
        ]);
        CurrentTimeStamp::mockTime('Dec 2 2019');
        $this->createDiscussion([
            'name' => 'Hello Discussion 1',
            'score' => 150,
            'body' => 'Dec 2 - 150 score'
        ]);
        self::$commentedDiscussionID = $this->lastInsertedDiscussionID;

        CurrentTimeStamp::mockTime('Dec 4 2019');
        $this->createComment([
            'name' => 'Hello comment',
            'body' => 'This is a comment body. Hello world, ipsum lorem, etc, Dec 4 - 40 score',
            'score' => 40,
        ]);

        // Make a more complicated category tree.

        $headingDepth1 = $this->createCategory([
            'name' => 'Heading Depth 1',
            'parentCategoryID' => -1,
            'displayAs' => 'heading',
        ])['categoryID'];
        $headingDepth2a = $this->createCategory([
            'name' => 'Heading Depth 2',
            'countComments' => 143,
            'displayAs' => 'heading',
        ])['categoryID'];

        CurrentTimeStamp::mockTime('Dec 3 2019');
        $this->createDiscussion([
            'name' => 'Hello Discussion 2',
            'score' => 100,
            'body' => 'Dec 3 - 100 score'
        ]);
        self::$commentedDiscussionID = $this->lastInsertedDiscussionID;
        $this->createComment(['name' => 'Hello comment', 'body' => 'This is a comment body. Hello world, ipsum lorem, etc']);
        $headingDepth2b = $this->createCategory([
            'name' => 'Heading Depth 2',
            'parentCategoryID' => $headingDepth1,
            'countComments' => 143,
            'displayAs' => 'heading',
        ])['categoryID'];



        $discussionsDepth1 = $this->createCategory([
            'name' => 'Discussions Depth 1',
            'parentCategoryID' => -1,
            'displayAs' => 'discussions',
        ])['categoryID'];
        $discussionsDepth2a = $this->createCategory([
            'name' => 'Discussions Depth 2',
        ])['categoryID'];
        $this->createDiscussion(['name' => 'Hello Discussion 3']);
        self::$commentedDiscussionID = $this->lastInsertedDiscussionID;
        $this->createComment(['name' => 'Hello comment', 'body' => 'This is a comment body. Hello world, ipsum lorem, etc']);
        $discussionsDepth2b = $this->createCategory([
            'name' => 'Discussions Depth 2',
            'parentCategoryID' => $discussionsDepth1,
        ])['categoryID'];

        $this->sortCategories([
            -1 => [$headingDepth1, $discussionsDepth1, $customCat],
        ]);

        $this->assertTrue(true);
    }

    /**
     * Prepare data for the tests.
     *
     * @param array $config
     * @param string $name
     *
     * @dataProvider provideDiscussionLists
     * @depends testSetup
     */
    public function testDiscussionList(array $config, string $name) {
        $this->runWithConfig($config, function () use ($name) {
            $this->generateStoryHtml('/discussions', $name);
        });
    }

    /**
     * @return array[]
     */
    public function provideDiscussionLists(): array {
        return [
            'Modern' => [
                ['Vanilla.Discussions.Layout' => 'modern'],
                'Discussion List (Modern)',
            ],
            'Table' => [
                ['Vanilla.Discussions.Layout' => 'modern'],
                'Discussion List (Modern)',
            ],
            'Admin Checks' => [
                ['Vanilla.AdminCheckboxes.Use' => true],
                'Discussion List (Admin Checks)',
            ],
        ];
    }

    /**
     * Test the category lists.
     *
     * @param array $config
     * @param string $name
     *
     * @dataProvider provideCategoryList
     * @depends testSetup
     */
    public function testCategoryList(array $config, string $name) {
        $this->runWithConfig($config, function () use ($name) {
            $this->generateStoryHtml('/categories', $name);
        });
    }

    /**
     * @return array[]
     */
    public function provideCategoryList(): array {
        return [
            'Modern' => [
                ['Vanilla.Categories.Layout' => 'modern'],
                'Category List (Modern)',
            ],
            'Table' => [
                ['Vanilla.Categories.Layout' => 'table'],
                'Category List (Table)',
            ],
            'Mixed' => [
                ['Vanilla.Categories.Layout' => 'mixed'],
                'Category List (Mixed)',
            ],
            'Foundation' => [
                ['Vanilla.Categories.Layout' => 'foundation'],
                'Category List (Foundation & Grid)',
            ],
        ];
    }

    /**
     * Test the category lists.
     *
     * @param array $config
     * @param string $name
     *
     * @dataProvider provideDiscussionCommentList
     * @depends testSetup
     */
    public function testDiscussionCommentList(array $config, string $name) {
        $this->runWithConfig($config, function () use ($name) {
            $id = self::$commentedDiscussionID;
            $this->generateStoryHtml("/discussion/{$id}", $name);
        });
    }

    /**
     * @return array[]
     */
    public function provideDiscussionCommentList(): array {
        return [
            'Normal' => [
                [],
                'Discussion Comment List',
            ],
            'Admin Checks' => [
                ['Vanilla.AdminCheckboxes.Use' => true],
                'Discussion Comment List (Checkboxes)',
            ],
        ];
    }

    /**
     * Test homepage rendering.
     *
     * @depends testSetup
     */
    public function testHomePage() {
        $afterBanner = function () {
            return \Gdn_Theme::module('PromotedContentModule', [
                'asHomeWidget' => true,
                'Selector' => 'score',
                'Selection' => 30,
                'Title' => 'This is promoted content',
            ]);
        };
        $this->getEventManager()->bind('afterBanner', $afterBanner);
        $this->generateStoryHtml('/', 'Home (Promoted Content)');
        $this->getEventManager()->unbind('afterBanner', $afterBanner);
    }
}
