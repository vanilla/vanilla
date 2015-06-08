<?php
/**
 * Manages the social plugins.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
        $this->title("Social Integration");
        $this->addSideMenu('dashboard/social');

        $Connections = $this->GetConnections();
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
        $Connections = $this->data('Connections', array());
        if (!is_array($Connections)) {
            $Connections = array();
        }

        foreach (Gdn::pluginManager()->AvailablePlugins() as $PluginKey => $PluginInfo) {
            if (!array_key_exists('SocialConnect', $PluginInfo)) {
                continue;
            }

            if (!array_key_exists($PluginKey, $Connections)) {
                $Connections[$PluginKey] = array();
            }

            $ConnectionName = $PluginInfo['Index'];

            if (Gdn::pluginManager()->CheckPlugin($PluginKey)) {
                $Configured = Gdn::pluginManager()->GetPluginInstance($ConnectionName, Gdn_PluginManager::ACCESS_PLUGINNAME)->IsConfigured();
            } else {
                $Configured = null;
            }

            $Connections[$PluginKey] = array_merge($Connections[$PluginKey], $PluginInfo, array(
                'Enabled' => Gdn::pluginManager()->CheckPlugin($PluginKey),
                'Configured' => $Configured
            ), array(
                'Icon' => sprintf("/plugins/%s/icon.png", $PluginInfo['Folder'])
            ));
        }

        return $Connections;
    }

    /**
     * Turn off a social plugin.
     *
     * @param $Plugin
     * @throws Exception
     */
    public function disable($Plugin) {
        $this->permission('Garden.Settings.Manage');
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
        WriteConnection($Connection);
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
        $Connections = $this->GetConnections();

        if (!array_key_exists($Plugin, $Connections)) {
            throw notFoundException('SocialConnect Plugin');
        }

        Gdn::pluginManager()->EnablePlugin($Plugin, null);

        $Connections = $this->GetConnections();
        $Connection = val($Plugin, $Connections);

        require_once($this->fetchViewLocation('connection_functions'));
        ob_start();
        WriteConnection($Connection);
        $Row = ob_get_clean();

        $this->jsonTarget("#Provider_{$Connection['Index']}", $Row);
        $this->informMessage(t("Plugin enabled."));

        unset($this->Data['Connections']);
        $this->render('blank', 'utility');
    }
}
