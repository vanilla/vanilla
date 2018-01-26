<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Web;

use Garden\Web\ViewInterface;
use Garden\Web\Data;

/**
 * Class JsonView
 */
class JsonView implements ViewInterface {

    /** @var WebLinking */
    private $webLinking;

    /**
     * JsonView constructor.
     *
     * @param WebLinking $webLinking
     */
    public function __construct(WebLinking $webLinking) {
        $this->webLinking = $webLinking;
    }

    /**
     * {@inheritdoc}
     */
    public function render(Data $data) {
        $paging = $data->getMeta('paging');

        // Handle pagination.
        if ($paging) {
            $hasPageCount = isset($paging['pageCount']);

            $firstPageUrl = str_replace('%s', 1, $paging['urlFormat']);
            $this->webLinking->addLink('first', $firstPageUrl);
            if ($paging['page'] > 1) {
                $prevPageUrl = $paging['page'] > 2 ? str_replace('%s', $paging['page'] - 1, $paging['urlFormat']) : $firstPageUrl;
                $this->webLinking->addLink('prev', $prevPageUrl);
            }
            if (($paging['more'] ?? false) || ($hasPageCount && $paging['page'] < $paging['pageCount'])) {
                $this->webLinking->addLink('next', str_replace('%s', $paging['page'] + 1, $paging['urlFormat']));
            }
            if ($hasPageCount) {
                $this->webLinking->addLink('last', str_replace('%s', $paging['pageCount'], $paging['urlFormat']));
            }
        }

        $data->setHeader('Link', $this->webLinking->getLinkHeaderValue());

        echo $data->render();
    }
}
