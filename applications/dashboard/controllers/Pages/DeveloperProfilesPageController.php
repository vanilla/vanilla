<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Models\DeveloperProfileModel;
use Vanilla\Utility\Timers;
use Vanilla\Web\PageDispatchController;

/**
 * GET /settings/vanilla-staff/profiles
 */
class DeveloperProfilesPageController extends PageDispatchController
{
    private DeveloperProfileModel $profilesModel;

    protected $assetSection = "admin-new";

    /**
     * Constructor.
     */
    public function __construct(DeveloperProfileModel $profilesModel)
    {
        $this->profilesModel = $profilesModel;
    }

    /**
     * GET /settings/vanilla-staff/profiles
     *
     * @return Data
     */
    public function index(): Data
    {
        Timers::instance()->setShouldRecordProfile(false);
        $page = $this->usePage(\DashboardPage::class)
            ->setSeoTitle("Developer - Recorded Profiles")
            ->setSeoRequired(false)
            ->permission("admin.only");

        return $page->render();
    }

    /**
     * GET /settings/vanilla-staff/profiles/:developerProfileID
     *
     * @param int $id
     *
     * @return Data
     */
    public function get(int $id): Data
    {
        Timers::instance()->setShouldRecordProfile(false);
        $page = $this->usePage(\DashboardPage::class)
            ->setSeoTitle("Developer - Profile Details")
            ->setSeoRequired(false)
            ->permission("admin.only");

        return $page->render();
    }

    /**
     * GET /settings/vanilla-staff/profiles/by-request-id/:cloudflareRayID
     *
     * @param string $path
     */
    public function get_byRequestID(string $path): void
    {
        Timers::instance()->setShouldRecordProfile(false);
        $this->useSimplePage("Developer - Profile Details By RequestID")->permission("admin.only");

        $recorded = $this->profilesModel->selectSingle([
            "requestID" => $path,
        ]);

        redirectTo("/developer/profiles/{$recorded["developerProfileID"]}");
    }
}
