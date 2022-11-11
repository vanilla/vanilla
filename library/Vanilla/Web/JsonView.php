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
class JsonView implements ViewInterface
{
    const CURRENT_PAGE_HEADER = "x-app-page-current";
    const TOTAL_COUNT_HEADER = "x-app-page-result-count";
    const LIMIT_HEADER = "x-app-page-limit";

    /**
     * {@inheritdoc}
     */
    public function render(Data $data)
    {
        $data->renderJson();
    }
}
