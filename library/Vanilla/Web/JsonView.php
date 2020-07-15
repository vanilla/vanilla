<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\ViewInterface;
use Garden\Web\Data;
use Vanilla\Web\Pagination\WebLinking;

/**
 * Class JsonView
 */
class JsonView implements ViewInterface {

    const CURRENT_PAGE_HEADER = 'x-app-page-current';
    const TOTAL_COUNT_HEADER = 'x-app-page-result-count';
    const LIMIT_HEADER = 'x-app-page-limit';

    /**
     * {@inheritdoc}
     */
    public function render(Data $data) {
        $paging = $data->getMeta('paging');

        // Handle pagination.
        if ($paging) {
            $links = new WebLinking();
            $hasPageCount = isset($paging['pageCount']);

            $firstPageUrl = str_replace('%s', 1, $paging['urlFormat']);
            $links->addLink('first', $firstPageUrl);
            if ($paging['page'] > 1) {
                $prevPageUrl = $paging['page'] > 2 ? str_replace('%s', $paging['page'] - 1, $paging['urlFormat']) : $firstPageUrl;
                $links->addLink('prev', $prevPageUrl);
            }
            if (($paging['more'] ?? false) || ($hasPageCount && $paging['page'] < $paging['pageCount'])) {
                $links->addLink('next', str_replace('%s', $paging['page'] + 1, $paging['urlFormat']));
            }
            if ($hasPageCount) {
                $links->addLink('last', str_replace('%s', $paging['pageCount'], $paging['urlFormat']));
            }
            $links->setHeader($data);

            $data->setHeader(self::CURRENT_PAGE_HEADER, $paging['page']);

            $totalCount = $paging['totalCount'] ?? null;
            if ($totalCount !== null) {
                $data->setHeader(self::TOTAL_COUNT_HEADER, $totalCount);
            }

            $limit = $paging['limit'] ?? null;
            if ($limit !== null) {
                $data->setHeader(self::LIMIT_HEADER, $limit);
            }
        }

        echo $data->render();
    }
}
