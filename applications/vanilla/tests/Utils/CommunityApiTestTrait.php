<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Utils;

use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Http\InternalClient;

/**
 * @method InternalClient api()
 */
trait CommunityApiTestTrait {

    /** @var int|null */
    protected $lastInsertedCategoryID = null;

    /** @var int|null */
    protected $lastInsertedDiscussionID = null;

    /** @var int|null */
    protected $lastInsertCommentID = null;

    /**
     * Clear local info between tests.
     */
    public function setUpCommunityApiTestTrait(): void {
        $this->lastInsertedCategoryID = null;
        $this->lastInsertedDiscussionID = null;
        $this->lastInsertCommentID = null;
    }

    /**
     * Create a category.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createCategory(array $overrides = []): array {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);
        $name = "Test Category $salt";
        $categoryID = $overrides['parentCategoryID'] ?? $this->lastInsertedCategoryID;

        $params = $overrides + [
                'customPermissions' => false,
                'displayAs' => 'discussions',
                'parentCategoryID' => $categoryID,
                'name' => $name,
                'urlCode' => slugify($name)
            ];
        $result = $this->api()->post('/categories', $params)->getBody();
        $this->lastInsertedCategoryID = $result['categoryID'];
        return $result;
    }

    /**
     * Create a discussion.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createDiscussion(array $overrides = []): array {
        $categoryID = $overrides['categoryID'] ?? $this->lastInsertedCategoryID;

        if ($categoryID === null) {
            throw new \Exception('Could not insert a test discussion because no category was specified.');
        }

        $params = $overrides + [
            'name' => 'Test Discussion',
            'format' => TextFormat::FORMAT_KEY,
            'body' => 'Hello Discussion',
            'categoryID' => $categoryID,
        ];
        $result = $this->api()->post('/discussions', $params)->getBody();
        $this->lastInsertedDiscussionID = $result['discussionID'];
        return $result;
    }

    /**
     * Create a discussion.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createComment(array $overrides = []): array {
        $discussionID = $overrides['discussionID'] ?? $this->lastInsertedDiscussionID;

        if ($discussionID === null) {
            throw new \Exception('Could not insert a test comment because no discussion was specified.');
        }

        $params = $overrides + [
            'format' => TextFormat::FORMAT_KEY,
            'body' => 'Hello Comment',
            'discussionID' => $discussionID,
        ];
        $result = $this->api()->post('/comments', $params)->getBody();
        $this->lastInsertCommentID = $result['commentID'];
        return $result;
    }
}
