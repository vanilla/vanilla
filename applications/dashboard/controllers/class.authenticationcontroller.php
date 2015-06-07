<?php
/**
 * Manages user authentication in Dashboard.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        if ($this->Menu) {
            $this->Menu->highlightRoute('/dashboard/authentication');
        }

        $this->enableSlicing($this);

        $Authenticators = Gdn::authenticator()->GetAvailable();
        $this->ChooserList = array();
        $this->ConfigureList = array();
        foreach ($Authenticators as $AuthAlias => $AuthConfig) {
            $this->ChooserList[$AuthAlias] = $AuthConfig['Name'];
            $Authenticator = Gdn::authenticator()->authenticateWith($AuthAlias);
            $ConfigURL = (is_a($Authenticator, "Gdn_Authenticator") && method_exists($Authenticator, 'AuthenticatorConfiguration')) ? $Authenticator->AuthenticatorConfiguration($this) : false;
            $this->ConfigureList[$AuthAlias] = $ConfigURL;
        }
        $this->CurrentAuthenticationAlias = Gdn::authenticator()->authenticateWith('default')->getAuthenticationSchemeAlias();
    }

    /**
     * Default method ('Choose' alias).
     *
     * @since 2.0.3
     * @access public
     *
     * @param string $AuthenticationSchemeAlias
     */
    public function index($AuthenticationSchemeAlias = null) {
        $this->View = 'choose';
        $this->choose($AuthenticationSchemeAlias);
    }

    /**
     * Select Authentication method.
     *
     * @since 2.0.3
     * @access public
     *
     * @param string $AuthenticationSchemeAlias
     */
    public function choose($AuthenticationSchemeAlias = null) {
        $this->permission('Garden.Settings.Manage');
        $this->addSideMenu('dashboard/authentication');
        $this->title(t('Authentication'));
        $this->addCssFile('authentication.css');

        $PreFocusAuthenticationScheme = null;
        if (!is_null($AuthenticationSchemeAlias)) {
            $PreFocusAuthenticationScheme = $AuthenticationSchemeAlias;
        }

        if ($this->Form->authenticatedPostback()) {
            $NewAuthSchemeAlias = $this->Form->getValue('Garden.Authentication.Chooser');
            $AuthenticatorInfo = Gdn::authenticator()->getAuthenticatorInfo($NewAuthSchemeAlias);
            if ($AuthenticatorInfo !== false) {
                $CurrentAuthenticatorAlias = Gdn::authenticator()->AuthenticateWith('default')->getAuthenticationSchemeAlias();

                // Disable current
                $AuthenticatorDisableEvent = "DisableAuthenticator".ucfirst($CurrentAuthenticatorAlias);
                $this->fireEvent($AuthenticatorDisableEvent);

                // Enable new
                $AuthenticatorEnableEvent = "EnableAuthenticator".ucfirst($NewAuthSchemeAlias);
                $this->fireEvent($AuthenticatorEnableEvent);

                $PreFocusAuthenticationScheme = $NewAuthSchemeAlias;
                $this->CurrentAuthenticationAlias = Gdn::authenticator()->authenticateWith('default')->getAuthenticationSchemeAlias();
            }
        }

        $this->setData('AuthenticationConfigureList', $this->ConfigureList);
        $this->setData('PreFocusAuthenticationScheme', $PreFocusAuthenticationScheme);
        $this->render();
    }

    /**
     * Configure authentication method.
     *
     * @since 2.0.3
     * @access public
     *
     * @param string $AuthenticationSchemeAlias
     */
    public function configure($AuthenticationSchemeAlias = null) {
        $Message = t("Please choose an authenticator to configure.");
        if (!is_null($AuthenticationSchemeAlias)) {
            $AuthenticatorInfo = Gdn::authenticator()->getAuthenticatorInfo($AuthenticationSchemeAlias);
            if ($AuthenticatorInfo !== false) {
                $this->AuthenticatorChoice = $AuthenticationSchemeAlias;
                if (array_key_exists($AuthenticationSchemeAlias, $this->ConfigureList) && $this->ConfigureList[$AuthenticationSchemeAlias] !== false) {
                    echo Gdn::slice($this->ConfigureList[$AuthenticationSchemeAlias]);
                    return;
                } else {
                    $Message = sprintf(t("The %s Authenticator does not have any custom configuration options."), $AuthenticatorInfo['Name']);
                }
            }
        }

        $this->setData('ConfigureMessage', $Message);
        $this->render();
    }
}
