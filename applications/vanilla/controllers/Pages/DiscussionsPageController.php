<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Vanilla\Controllers\Pages;


use Vanilla\ApiUtils;
use Vanilla\Web\Controller;

class DiscussionsPageController extends Controller {
    /**
     * @var \DiscussionsApiController
     */
    private $discussionsApi;

    public function __construct(
        \DiscussionsApiController $discussionsApi
    ) {
        $this->discussionsApi = $discussionsApi;
    }

    public function index($page = '') {
        $page = ApiUtils::pageNumber($page);

        $data = [
            'discussions' => $this->discussionsApi->index(['page' => $page, 'expand' => true])
        ];

        return $data;
    }
}
