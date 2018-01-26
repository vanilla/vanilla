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
        $meta = $data['discussions']->getMetaArray();
        $meta['paging']['urlFormat'] = url('/discussions/p', true).'%s';

        return new Data($data, $meta);
    }

    public function get_bookmarkedDropdownContents(array $query) {
        $result = new Data([]);
        $result->addData($this->discussionsApi->get_bookmarked($query), 'discussions', true);
        $result->addMeta('paging', 'urlFormat', url('/discussions/bookmarked', true).'?page=%s');
        $result->setMeta('template', 'discussion-bookmarked-dropdown-contents');
        $result->setMeta('master', 'empty-master');

        return $result;
    }
}
