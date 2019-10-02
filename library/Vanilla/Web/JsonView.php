<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\ViewInterface;
use Garden\Web\Data;

/**
 * Class JsonView
 */
class JsonView implements ViewInterface {
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
        }

        echo $data->render();
    }
}
