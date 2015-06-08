<?php
/**
 * Manages the embedding of a forum on a foreign page.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0.18
 */

/**
 * Handles /embed endpoint.
 */
class EmbedController extends DashboardController {

    /** @var array Models to include. */
    public $Uses = array('Database', 'Form');

    /**
     * Default method.
     */
    public function index() {
        redirect('embed/comments');
    }

    /**
     * Run before
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
    }

    /**
     * Display the embedded forum.
     *
     * @since 2.0.18
     * @access public
     */
    public function comments($Toggle = '', $TransientKey = '') {
        $this->permission('Garden.Settings.Manage');

        try {
            if ($this->toggle($Toggle, $TransientKey)) {
                redirect('embed/comments');
            }
        } catch (Gdn_UserException $Ex) {
            $this->Form->addError($Ex);
        }

        $this->addSideMenu('dashboard/embed/comments');
        $this->Form = new Gdn_Form();
        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(array('Garden.Embed.CommentsPerPage', 'Garden.Embed.SortComments', 'Garden.Embed.PageToForum'));

        $this->Form->setModel($ConfigurationModel);
        if ($this->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $this->Form->setData($ConfigurationModel->Data);
        } else {
            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
            }
        }

        $this->title(t('Blog Comments'));
        $this->render();
    }

    /**
     * Embed the entire forum.
     *
     * @param string $Toggle
     * @param string $TransientKey
     */
    public function forum($Toggle = '', $TransientKey = '') {
        $this->permission('Garden.Settings.Manage');

        try {
            if ($this->toggle($Toggle, $TransientKey)) {
                redirect('embed/forum');
            }
        } catch (Gdn_UserException $Ex) {
            $this->Form->addError($Ex);
        }

        $this->addSideMenu('dashboard/embed/forum');
        $this->title('Embed Forum');
        $this->render();
    }

    /**
     * Options page.
     *
     * @param string $Toggle
     * @param string $TransientKey
     */
    public function advanced($Toggle = '', $TransientKey = '') {
        $this->permission('Garden.Settings.Manage');

        try {
            if ($this->toggle($Toggle, $TransientKey)) {
                redirect('embed/advanced');
            }
        } catch (Gdn_UserException $Ex) {
            $this->Form->addError($Ex);
        }

        $this->title('Advanced Embed Settings');

        $this->addSideMenu('dashboard/embed/advanced');
        $this->Form = new Gdn_Form();

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(array('Garden.TrustedDomains', 'Garden.Embed.RemoteUrl', 'Garden.Embed.ForceDashboard', 'Garden.Embed.ForceForum', 'Garden.SignIn.Popup'));

        $this->Form->setModel($ConfigurationModel);
        if ($this->Form->authenticatedPostBack() === false) {
            // Format trusted domains as a string
            $TrustedDomains = val('Garden.TrustedDomains', $ConfigurationModel->Data);
            if (is_array($TrustedDomains)) {
                $TrustedDomains = implode("\n", $TrustedDomains);
            }

            $ConfigurationModel->Data['Garden.TrustedDomains'] = $TrustedDomains;

            // Apply the config settings to the form.
            $this->Form->setData($ConfigurationModel->Data);
        } else {
            // Format the trusted domains as an array based on newlines & spaces
            $TrustedDomains = $this->Form->getValue('Garden.TrustedDomains');
            $TrustedDomains = explode("\n", $TrustedDomains);
            $TrustedDomains = array_unique(array_filter(array_map('trim', $TrustedDomains)));
            $TrustedDomains = implode("\n", $TrustedDomains);
            $this->Form->setFormValue('Garden.TrustedDomains', $TrustedDomains);
            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
            }

            // Reformat array as string so it displays properly in the form
            $this->Form->setFormValue('Garden.TrustedDomains', $TrustedDomains);
        }

        $this->permission('Garden.Settings.Manage');
        $this->render();
    }

    /**
     * Handle toggling this version of embedding on and off. Take care of disabling the other version of embed (the old plugin).
     *
     * @param string $Toggle
     * @param string $TransientKey
     * @return boolean
     * @throws Gdn_UserException
     */
    private function toggle($Toggle = '', $TransientKey = '') {
        if (in_array($Toggle, array('enable', 'disable')) && Gdn::session()->validateTransientKey($TransientKey)) {
            if ($Toggle == 'enable' && array_key_exists('embedvanilla', Gdn::pluginManager()->enabledPlugins())) {
                throw new Gdn_UserException('You must disable the "Embed Vanilla" plugin before continuing.');
            }

            // Do the toggle
            saveToConfig('Garden.Embed.Allow', $Toggle == 'enable' ? true : false);
            return true;
        }
        return false;
    }

    /**
     * Allow for a custom embed theme.
     *
     * @since 2.0.18
     * @access public
     */
    public function theme() {
        // Do nothing by default
    }
}
