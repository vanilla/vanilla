<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Controller for the content management dashboard.
 */
class ContentController extends DashboardController
{
    public function __construct()
    {
        parent::__construct();
        $this->PageName = "dashboard";
    }

    public function initialize()
    {
        parent::initialize();
        Gdn_Theme::section("Moderation");
        if ($this->Menu) {
            $this->Menu->highlightRoute("/dashboard/content/reports");
        }
    }

    public function index()
    {
        if ($this->Menu) {
            $this->Menu->highlightRoute("/dashboard/content/reports");
        }
        $this->setData("Title", t("Reports"));
        $this->renderReact();
    }
}
