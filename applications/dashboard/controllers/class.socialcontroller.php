<?php
/**
 * Manages the social plugins.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.1
 */

/**
 * Handles /social endpoint, so it must be an extrovert.
 */
class SocialController extends DashboardController {

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
        redirect('social/manage');
    }

    /**
     * Settings page.
     */
    public function manage() {
        $this->permission('Garden.Settings.Manage');
        $this->title("Social Connect Addons");
        $this->setHighlightRoute('/social/manage');

        $Connections = $this->getConnections();
        $this->setData('Connections', $Connections);

        $this->render();
    }

    /**
     * Find available social plugins.
     *
     * @return array|mixed
     * @throws Exception
     */
    protected function getConnections() {
        $this->fireEvent('GetConnections');
        $connections = [];

        $addons = Gdn::addonManager()->lookupAllByType(\Vanilla\Addon::TYPE_ADDON);

        foreach ($addons as $addonName => $addon) {
            $addonInfo = $addon->getInfo();

            // Limit to designated social addons.
            if (!array_key_exists('socialConnect', $addonInfo)) {
                continue;
            }

            // See if addon is enabled.
            $isEnabled = Gdn::addonManager()->isEnabled($addonName, \Vanilla\Addon::TYPE_ADDON);
            setValue('enabled', $addonInfo, $isEnabled);

            // See if we can detect whether connection is configured.
            $isConfigured = null;
            if ($isEnabled) {
                $pluginInstance = Gdn::pluginManager()->getPluginInstance($addonName, Gdn_PluginManager::ACCESS_PLUGINNAME);
                if (method_exists($pluginInstance, 'isConfigured')) {
                    $isConfigured = $pluginInstance->isConfigured();
                }
            }
            setValue('configured', $addonInfo, $isConfigured);

            // Add the connection.
            $connections[$addonName] = $addonInfo;
        }

        return $connections;
    }

    /**
     * Turn off a social plugin.
     *
     * @param $Plugin
     * @throws Exception
     */
    public function disable($Plugin) {
        $this->permission('Garden.Settings.Manage');
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $Connections = $this->GetConnections();
        unset($this->Data['Connections']);

        if (!array_key_exists($Plugin, $Connections)) {
            throw notFoundException('SocialConnect Plugin');
        }

        Gdn::pluginManager()->DisablePlugin($Plugin);

        $Connections = $this->GetConnections();
        $Connection = val($Plugin, $Connections);

        require_once($this->fetchViewLocation('connection_functions'));
        ob_start();
        WriteConnection($Connection, false);
        $Row = ob_get_clean();

        $this->jsonTarget("#Provider_{$Connection['Index']}", $Row);
        $this->informMessage(t("Plugin disabled."));

        unset($this->Data['Connections']);
        $this->render('blank', 'utility');
    }

    /**
     * Turn on a social plugin.
     *
     * @param $Plugin
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function enable($Plugin) {
        $this->permission('Garden.Settings.Manage');
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $Connections = $this->GetConnections();

        if (!array_key_exists($Plugin, $Connections)) {
            throw notFoundException('SocialConnect Plugin');
        }

        Gdn::pluginManager()->EnablePlugin($Plugin, null);

        $Connections = $this->GetConnections();
        $Connection = val($Plugin, $Connections);

        require_once($this->fetchViewLocation('connection_functions'));
        ob_start();
        WriteConnection($Connection, false);
        $Row = ob_get_clean();

//        $this->informMessage(t("Plugin enabled."));
        $this->jsonTarget("#Provider_{$Connection['Index']}", $Row);

        unset($this->Data['Connections']);
        $this->render('blank', 'utility');
    }
}
