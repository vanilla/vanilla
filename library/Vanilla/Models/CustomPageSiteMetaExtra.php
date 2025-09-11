<?php
/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Models;

/**
 * Class for adding extra site meta related to custom pages.
 */
class CustomPageSiteMetaExtra extends SiteMetaExtra
{
    /**
     * DI.
     *
     * @param CustomPageModel $customPageModel
     */
    public function __construct(private CustomPageModel $customPageModel)
    {
    }

    /**
     * Get active custom pages to add to the site meta.
     * These are the custom pages that the current user can view, with pageID and url.
     *
     * @return array
     */
    public function getValue(): array
    {
        $customPages = $this->customPageModel->getActiveUrlPathsForUser();

        return [
            "customPages" => $customPages,
        ];
    }
}
