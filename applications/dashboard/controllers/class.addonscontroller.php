<?php
/**
 * Manages addons lists.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.4
 */

/**
 * Handles management of addon groups and their respective settings pages.
 */
class AddonsController extends DashboardController {

    /** @var array Models to automatically instantiate. */
    public $Uses = array('Form', 'Database');

    /**
     * Runs before every call to this controller.
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
    }

    /**
     * Default method.
     */
    public function index() {
        redirect('addons/socialconnect');
    }

    /**
     * Social Connect addons.
     */
    public function socialConnect() {
        $this->permission('Garden.Settings.Manage');
        $this->title(t("Social Connect Addons"));
        $this->setHighlightRoute('/addons/socialconnect');

        // Addons list data.
        $addons = $this->getGroup('SocialConnect');
        $this->setData('addons', $addons);

        // Set view details.
        $this->setData('title', t("Social Connect Addons"));
        $this->setData('help.title', t("What's This?"));
        $this->setData('help.description', t('Here is a list of all your social addons.',
            "Here's a list of all your social addons. You can enable, disable, and configure them from this page."));
        $this->View = 'list';
        $this->render();
    }

    /**
     * Single Sign On addons.
     */
    public function sso() {
        $this->permission('Garden.Settings.Manage');
        $this->title(t("Single Sign On Addons"));
        $this->setHighlightRoute('/addons/sso');

        // Addons list data.
        $addons = $this->getGroup('SSO');
        $this->setData('addons', $addons);

        // Set view details.
        $this->setData('title', t("Single Sign On Addons"));
        $this->setData('help.title', t("What's This?"));
        $this->setData('help.description', t('Here is a list of all your SSO addons.',
            "Here's a list of all your SSO addons. You can enable, disable, and configure them from this page."));
        $this->View = 'list';
        $this->render();
    }


    /**
     * Find available addons in designated group.
     *
     * @param string $type A key that some addons use in their 'Group' info field.
     * @return array
     * @throws Exception
     */
    protected function getGroup($type) {
        $this->fireEvent('GetConnections');
        $group = [];

        $addons = Gdn::addonManager()->lookupAllByType(\Vanilla\Addon::TYPE_ADDON);

        foreach ($addons as $addonName => $addon) {
            $addonInfo = $addon->getInfo();

            // Limit to group specified.
            if (!(strtolower(val('group', $addonInfo)) === strtolower($type))) {
                continue;
            }

            // See if addon is enabled.
            $isEnabled = Gdn::addonManager()->isEnabled($addonName, \Vanilla\Addon::TYPE_ADDON);
            setValue('enabled', $addonInfo, $isEnabled);

            // Add the connection.
            $group[$addonName] = $addonInfo;
        }

        return $group;
    }
}
