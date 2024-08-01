<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Controllers\Pages;

use Vanilla\Web\PageDispatchController;

/**
 * Controller for /utility/convert-html/:format page.
 */
class ConvertHTMLPageController extends PageDispatchController
{
    /**
     * Handle /utility/convert-html/:format
     *
     * @param array $query
     * @return \Garden\Web\Data
     */
    public function index(string $format, string $recordType, string $recordID)
    {
        return $this->useSimplePage("Convert HTML")
            ->setCanonicalUrl("/utility/convert-html/$format/$recordType/$recordID")
            ->setSeoTitle(t("Convert HTML"))
            ->blockRobots()
            ->render();
    }
}
