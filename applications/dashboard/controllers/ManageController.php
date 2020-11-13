<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * A react wrapper for community management UI.
 */
class ManageController extends DashboardController {
    public const FEATURE_ROLE_APPLICATIONS = 'roleApplications';

    /**
     * @inheritDoc
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Moderation');
    }


    /**
     * Serve all paths.
     *
     * @param string|null $path Any path.
     */
    public function requests(string $path = null) {
        $this->permission('Garden.Community.Manage');
        $this->title(t("Requests"));
        $this->setHighlightRoute("manage/requests/$path");

        switch ($path) {
            case 'role-applications':
                \Vanilla\FeatureFlagHelper::ensureFeature(self::FEATURE_ROLE_APPLICATIONS);
                break;
            default:
                throw notFoundException();
        }
        $this->renderReact();
    }
}
