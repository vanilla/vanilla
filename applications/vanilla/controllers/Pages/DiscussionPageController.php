<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Vanilla\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\ApiUtils;
use Vanilla\Web\Controller;

class DiscussionPageController extends Controller {
    /**
     * @var \DiscussionsApiController
     */
    private $discussionsApi;

    /**
     * @var \CommentsApiController
     */
    private $commentsApi;

    public function __construct(
        \DiscussionsApiController $discussionsApi,
        \CommentsApiController $commentsApi
    ) {
        $this->discussionsApi = $discussionsApi;
        $this->commentsApi = $commentsApi;
    }

    public function get($id, $slug = '', $page = '') {
        $discussion = $this->discussionsApi->get($id, []);

        $query = ['discussionID' => $discussion['discussionID'], 'page' => ApiUtils::pageNumber($page), 'expand' => true];
        $comments = $this->commentsApi->index($query);

        $result = new Data(['discussion' => $discussion, 'comments' => $comments]);

        return $result;
    }
}
