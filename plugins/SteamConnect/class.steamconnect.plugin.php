<?php if (!defined('APPLICATION')) exit;

/**
 * Steam Connect Plugin
 *
 * @author    Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2014 (c) Vanilla Forums Inc
 * @package   Steam Connect
 * @since     1.0.0
 */
class SteamConnectPlugin extends Gdn_Plugin {
    /**
     * This will run when you "Enable" the plugin
     *
     * @return bool
     */
    public function setup() {

    }

    /**
     * Check whether this addon has enough configuration to work.
     *
     * @return mixed
     */
    public function isConfig() {
        return c('Plugins.SteamConnect.APIKey', FALSE);
    }

    /**
     * Retrieve the URL to start an auth request.
     *
     * @param bool $popup
     * @return string
     */
    protected function _AuthorizeHref($popup = FALSE) {
        $url = url('/entry/openid', TRUE);
        $urlParts = explode('?', $url);
        parse_str(getValue(1, $urlParts, ''), $query);
        $query['url'] = 'https://steamcommunity.com/openid';
        $path = '/'.Gdn::request()->path();
        $query['Target'] = getValue('Target', $_GET, $path ? $path : '/');
        if ($popup) {
            $query['display'] = 'popup';
        }

        $result = $urlParts[0].'?'.http_build_query($query);
        return $result;
    }

    /**
     * Add Steam as an option to the normal signin page.
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function entryController_signIn_handler($sender, $args) {
        if (isset($sender->Data['Methods']) && $this->isConfig()) {
            $url = $this->_AuthorizeHref();

            // Add the steam method to the controller.
            $method = [
                'Name' => 'Steam',
                'SignInHtml' => socialSigninButton('Steam', $url, 'button', ['class' => 'js-extern'])
            ];

            $sender->Data['Methods'][] = $method;
        }
    }

    /**
     * Add Steam-Connect button to MeModule
     *
     * @param MeModule $sender
     * @param array $args
     */
    public function base_signInIcons_handler($sender, $args) {
        if ($this->isConfig()) {
            echo "\n".$this->_GetButton();
        }
    }

    /**
     * Inject a sign-in icon into the ME menu.
     *
     * @param Gdn_Controller $sender.
     * @param array $args.
     */
    public function base_beforeSignInButton_handler($sender, $args) {
        if ($this->isConfig()) {
            echo "\n".$this->_GetButton();
        }
    }

    /**
     * Insert css file for custom styling of signin button/icon.
     *
     * @param AssetModel $sender
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('steam-connect.css', 'plugins/SteamConnect');
    }

    /**
     * Build-A-Button.
     *
     * @return string|null
     */
    private function _GetButton() {
        if ($this->isConfig()) {
            $url = $this->_AuthorizeHref();
            return socialSigninButton('Steam', $url, 'icon', ['class' => 'js-extern']);
        }
    }

    /**
     * Add Steam-Connect button to default mobile theme.
     *
     * @param DiscussionsController $sender
     */
    public function base_beforeSignInLink_handler($sender) {
        if (!Gdn::session()->isValid() && $this->isConfig()) {
            echo "\n".wrap($this->_GetButton(), 'li', ['class' => 'Connect SteamConnect']);
        }
    }

    /**
     * Capture the user's UniqueID being sent back from provider and saving it to the session.
     *
     * @param OpenIDPlugin $sender
     * @param array $args
     */
    public function openIDPlugin_afterConnectData_handler($sender, $args) {
        $form = $args['Form'];
        $openID = $args['OpenID'];
        $steamID = $this->getSteamID($openID);

        // Make a call to steam.
        $qs = [
            'key' => c('Plugins.SteamConnect.APIKey'),
            'steamids' => $steamID
        ];

        $url = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?'.http_build_query($qs);

        $json_object= file_get_contents($url);
        $json_decoded = json_decode($json_object);

        $player = $json_decoded->response->players[0];

        $form->setFormValue('Provider', 'Steam');
        $form->setFormValue('ProviderName', 'Steam');
        $form->setFormValue('UniqueID', $steamID);
        $form->setFormValue('Photo', $player->avatarfull);

        /**
         * Check to see if we already have an authentication record for this user.  If we don't, setup their username.
         */
        if (!Gdn::userModel()->getAuthentication($steamID, 'Steam')) {
            $form->setFormValue('Name', $player->personaname);
        }

        if (isset($player->realname)) {
            $form->setFormValue('FullName', $player->realname);
        }
    }

    /**
     * Get steam id
     *
     * @param object $openID
     * @return string|null
     */
    public function getSteamID($openID) {
        $ptn = "/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
        preg_match($ptn, $openID->identity, $matches);
        return $matches[1];
    }

    /**
     * Steam connect settings page.
     *
     * @param SettingsControler $sender
     */
    public function settingsController_steamConnect_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $aPIKeyDescription =  '<div class="info">'.sprintf(t('A %s is necessary for this plugin to work.'), t('Steam Web API Key')).' '
            .sprintf(t('Don\'t have a %s?'), t('Steam Web API Key'))
            .' <a href="https://steamcommunity.com/dev/apikey">'.t('Get one here.').'</a></div>';

        $conf = new ConfigurationModule($sender);
        $conf->initialize([
            'Plugins.SteamConnect.APIKey' => ['Control' => 'TextBox', 'LabelCode' => 'Steam Web API Key', 'Description' => $aPIKeyDescription]
        ]);

        $sender->addSideMenu();
        $sender->setData('Title', sprintf(t('%s Settings'), t('Steam Connect')));
        $sender->ConfigurationModule = $conf;
        $conf->renderAll();
    }
}
