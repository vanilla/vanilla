<?php if (!defined('APPLICATION')) exit;

$PluginInfo['Steam'] = array(
    'Name'        => "Steam",
    'Description' => "Allow users to sign in with their Steam Account. Requires &lsquo;OpenID&rsquo; plugin to be enabled first.",
    'Version'     => '1.0.0',
    'RequiredPlugins' => array('OpenID' => '0.1a'),
    'MobileFriendly' => TRUE,
    'Author'      => "Becky Van Bussel",
    'AuthorEmail' => 'becky@vanillaforums.com',
    'AuthorUrl'   => 'http://vanillaforums.com',
    'License'     => 'Proprietary',
    'SettingsUrl' => '/settings/steam',
    'SettingsPermission' => 'Garden.Settings.Manage'
);

/**
 * Steam Plugin
 *
 * @author    Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2014 (c) Vanilla Forums Inc
 * @license   Proprietary
 * @package   Steam
 * @since     1.0.0
 */
class SteamPlugin extends Gdn_Plugin {
    /**
     * This will run when you "Enable" the plugin
     *
     * @since  1.0.0
     * @access public
     * @return bool
     */
    public function setup() {

    }

    public function isConfig() {
        return C('Plugins.Steam.APIKey');
    }

    protected function _AuthorizeHref($Popup = FALSE) {
        $Url = Url('/entry/openid', TRUE);
        $UrlParts = explode('?', $Url);
        parse_str(GetValue(1, $UrlParts, ''), $Query);
        $Query['url'] = 'http://steamcommunity.com/openid';
        $Path = '/'.Gdn::Request()->Path();
        $Query['Target'] = GetValue('Target', $_GET, $Path ? $Path : '/');
        if ($Popup)
            $Query['display'] = 'popup';

        $Result = $UrlParts[0].'?'.http_build_query($Query);
        return $Result;
    }


    /// Plugin Event Handlers ///

    /**
     *
     * @param Gdn_Controller $Sender
     */
    public function EntryController_SignIn_Handler($Sender, $Args) {

        if (isset($Sender->Data['Methods']) && $this->isConfig()) {
            $Url = $this->_AuthorizeHref();

            // Add the steam method to the controller.
            $Method = array(
                'Name' => 'Steam',
                'SignInHtml' => SocialSigninButton('Steam', $Url, 'button', array('class' => 'js-extern'))
            );

            $Sender->Data['Methods'][] = $Method;
        }
    }

    public function Base_SignInIcons_Handler($Sender, $Args) {
        if ($this->isConfig()) {
            echo "\n".$this->_GetButton();
        }
    }

    public function Base_BeforeSignInButton_Handler($Sender, $Args) {
        if ($this->isConfig()) {
            echo "\n".$this->_GetButton();
        }
    }

    private function _GetButton() {
        if ($this->isConfig()) {
            $Url = $this->_AuthorizeHref();
            return SocialSigninButton('Steam', $Url, 'icon', array('class' => 'js-extern'));
        }
    }

    public function Base_BeforeSignInLink_Handler($Sender) {
        if (!Gdn::Session()->IsValid() && $this->isConfig()) {
            echo "\n".Wrap($this->_GetButton(), 'li', array('class' => 'Connect SteamConnect'));
        }
    }

    public function OpenIDPlugin_AfterConnectData_Handler($Sender, $Args) {

        $Form = $Args['Form'];
        $OpenID = $Args['OpenID'];
        $SteamID = $this->getSteamID($OpenID);

        // Make a call to steam.
        $qs = array(
            'key' => C('Plugins.Steam.APIKey'),
            'steamids' => $SteamID
        );

        $url = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?'.http_build_query($qs);

        $json_object= file_get_contents($url);
        $json_decoded = json_decode($json_object);

        $player = $json_decoded->response->players[0];

        $Form->SetFormValue('Provider', 'Steam');
        $Form->SetFormValue('ProviderName', 'Steam');
        $Form->SetFormValue('UniqueID', $OpenID->identity);
        $Form->SetFormValue('UserID', $SteamID);
        $Form->SetFormValue('Name', $player->personaname);
        $Form->SetFormValue('ConnectName', $player->personaname);
        $Form->SetFormValue('Photo', $player->avatarfull);
        if (isset($player->realname)) {
            $Form->SetFormValue('FullName', $player->realname);
        }
    }

    public function getSteamID($OpenID) {
        $ptn = "/^http:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
        preg_match($ptn, $OpenID->identity, $matches);
        return $matches[1];
    }

    public function SettingsController_Steam_Create($Sender) {
        $Sender->Permission('Garden.Settings.Manage');

        $APIKeyDescription =  '<div class="help">'.sprintf(T('A %s is necessary for this plugin to work.'), T('Steam Web API Key')).' '
            .sprintf(T('Don\'t have a %s?'), T('Steam Web API Key'))
            .' <a href="http://steamcommunity.com/dev/apikey">'.T('Get one here.').'</a>';

        $Conf = new ConfigurationModule($Sender);
        $Conf->Initialize(array(
            'Plugins.Steam.APIKey' => array('Control' => 'TextBox', 'LabelCode' => 'Steam Web API Key', 'Options' => array('class' => 'InputBox BigInput'), 'Description' => $APIKeyDescription)
        ));

        $Sender->AddSideMenu();
        $Sender->SetData('Title', sprintf(T('%s Settings'), T('Steam')));
        $Sender->ConfigurationModule = $Conf;
        $Conf->RenderAll();
    }
}
