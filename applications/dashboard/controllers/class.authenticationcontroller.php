<?php
/**
 * Manages user authentication in Dashboard.
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0.3
 */

/**
 * Authentication manager.
 */
class AuthenticationController extends DashboardController {
    /**
     * Models to include.
     *
     * @since 2.0.3
     * @access public
     * @var array
     */
    public $Uses = array('Form', 'Database');

    /**
     * @see /library/core/class.controller.php
     */
    public $ModuleSortContainer = 'Dashboard';

    /**
     * Garden's form object.
     *
     * @since 2.0.3
     * @access public
     * @var Gdn_Form
     */
    public $Form;

    /**
     * Highlight route and do authenticator setup.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.3
     * @access public
     */
    public function Initialize() {
        parent::Initialize();
        Gdn_Theme::Section('Dashboard');
        if ($this->Menu)
            $this->Menu->HighlightRoute('/dashboard/authentication');

        $this->EnableSlicing($this);

        $Authenticators = Gdn::Authenticator()->GetAvailable();
        $this->ChooserList = array();
        $this->ConfigureList = array();
        foreach ($Authenticators as $AuthAlias => $AuthConfig) {
            $this->ChooserList[$AuthAlias] = $AuthConfig['Name'];
            $Authenticator = Gdn::Authenticator()->AuthenticateWith($AuthAlias);
            $ConfigURL = (is_a($Authenticator, "Gdn_Authenticator") && method_exists($Authenticator, 'AuthenticatorConfiguration')) ? $Authenticator->AuthenticatorConfiguration($this) : FALSE;
            $this->ConfigureList[$AuthAlias] = $ConfigURL;
        }
        $this->CurrentAuthenticationAlias = Gdn::Authenticator()->AuthenticateWith('default')->GetAuthenticationSchemeAlias();
    }

    /**
     * Default method ('Choose' alias).
     *
     * @since 2.0.3
     * @access public
     *
     * @param string $AuthenticationSchemeAlias
     */
    public function Index($AuthenticationSchemeAlias = NULL) {
        $this->View = 'choose';
        $this->Choose($AuthenticationSchemeAlias);
    }

    /**
     * Select Authentication method.
     *
     * @since 2.0.3
     * @access public
     *
     * @param string $AuthenticationSchemeAlias
     */
    public function Choose($AuthenticationSchemeAlias = NULL) {
        $this->Permission('Garden.Settings.Manage');
        $this->AddSideMenu('dashboard/authentication');
        $this->Title(T('Authentication'));
        $this->AddCssFile('authentication.css');

        $PreFocusAuthenticationScheme = NULL;
        if (!is_null($AuthenticationSchemeAlias))
            $PreFocusAuthenticationScheme = $AuthenticationSchemeAlias;

        if ($this->Form->AuthenticatedPostback()) {
            $NewAuthSchemeAlias = $this->Form->GetValue('Garden.Authentication.Chooser');
            $AuthenticatorInfo = Gdn::Authenticator()->GetAuthenticatorInfo($NewAuthSchemeAlias);
            if ($AuthenticatorInfo !== FALSE) {
                $CurrentAuthenticatorAlias = Gdn::Authenticator()->AuthenticateWith('default')->GetAuthenticationSchemeAlias();

                // Disable current
                $AuthenticatorDisableEvent = "DisableAuthenticator".ucfirst($CurrentAuthenticatorAlias);
                $this->FireEvent($AuthenticatorDisableEvent);

                // Enable new
                $AuthenticatorEnableEvent = "EnableAuthenticator".ucfirst($NewAuthSchemeAlias);
                $this->FireEvent($AuthenticatorEnableEvent);

                $PreFocusAuthenticationScheme = $NewAuthSchemeAlias;
                $this->CurrentAuthenticationAlias = Gdn::Authenticator()->AuthenticateWith('default')->GetAuthenticationSchemeAlias();
            }
        }

        $this->SetData('AuthenticationConfigureList', $this->ConfigureList);
        $this->SetData('PreFocusAuthenticationScheme', $PreFocusAuthenticationScheme);
        $this->Render();
    }

    /**
     * Configure authentication method.
     *
     * @since 2.0.3
     * @access public
     *
     * @param string $AuthenticationSchemeAlias
     */
    public function Configure($AuthenticationSchemeAlias = NULL) {
        $Message = T("Please choose an authenticator to configure.");
        if (!is_null($AuthenticationSchemeAlias)) {
            $AuthenticatorInfo = Gdn::Authenticator()->GetAuthenticatorInfo($AuthenticationSchemeAlias);
            if ($AuthenticatorInfo !== FALSE) {
                $this->AuthenticatorChoice = $AuthenticationSchemeAlias;
                if (array_key_exists($AuthenticationSchemeAlias, $this->ConfigureList) && $this->ConfigureList[$AuthenticationSchemeAlias] !== FALSE) {
                    echo Gdn::Slice($this->ConfigureList[$AuthenticationSchemeAlias]);
                    return;
                } else
                    $Message = sprintf(T("The %s Authenticator does not have any custom configuration options."), $AuthenticatorInfo['Name']);
            }
        }

        $this->SetData('ConfigureMessage', $Message);
        $this->Render();
    }

}
