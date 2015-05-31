<?php
/**
 * Manages the embedding of a forum on a foreign page.
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
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
    public function Index() {
        Redirect('embed/comments');
    }

    /**
     * Run before
     */
    public function Initialize() {
        parent::Initialize();
        Gdn_Theme::Section('Dashboard');
    }

    /**
     * Display the embedded forum.
     *
     * @since 2.0.18
     * @access public
     */
    public function Comments($Toggle = '', $TransientKey = '') {
        $this->Permission('Garden.Settings.Manage');

        try {
            if ($this->Toggle($Toggle, $TransientKey))
                Redirect('embed/comments');
        } catch (Gdn_UserException $Ex) {
            $this->Form->AddError($Ex);
        }

        $this->AddSideMenu('dashboard/embed/comments');
        $this->Form = new Gdn_Form();
        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->SetField(array('Garden.Embed.CommentsPerPage', 'Garden.Embed.SortComments', 'Garden.Embed.PageToForum'));

        $this->Form->SetModel($ConfigurationModel);
        if ($this->Form->AuthenticatedPostBack() === FALSE) {
            // Apply the config settings to the form.
            $this->Form->SetData($ConfigurationModel->Data);
        } else {
            if ($this->Form->Save() !== FALSE)
                $this->InformMessage(T("Your settings have been saved."));
        }

        $this->Title(T('Blog Comments'));
        $this->Render();
    }

    /**
     * Embed the entire forum.
     *
     * @param string $Toggle
     * @param string $TransientKey
     */
    public function Forum($Toggle = '', $TransientKey = '') {
        $this->Permission('Garden.Settings.Manage');

        try {
            if ($this->Toggle($Toggle, $TransientKey))
                Redirect('embed/forum');
        } catch (Gdn_UserException $Ex) {
            $this->Form->AddError($Ex);
        }

        $this->AddSideMenu('dashboard/embed/forum');
        $this->Title('Embed Forum');
        $this->Render();
    }

    /**
     * Options page.
     *
     * @param string $Toggle
     * @param string $TransientKey
     */
    public function Advanced($Toggle = '', $TransientKey = '') {
        $this->Permission('Garden.Settings.Manage');

        try {
            if ($this->Toggle($Toggle, $TransientKey))
                Redirect('embed/advanced');
        } catch (Gdn_UserException $Ex) {
            $this->Form->AddError($Ex);
        }

        $this->Title('Advanced Embed Settings');

        $this->AddSideMenu('dashboard/embed/advanced');
        $this->Form = new Gdn_Form();

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->SetField(array('Garden.TrustedDomains', 'Garden.Embed.RemoteUrl', 'Garden.Embed.ForceDashboard', 'Garden.Embed.ForceForum', 'Garden.SignIn.Popup'));

        $this->Form->SetModel($ConfigurationModel);
        if ($this->Form->AuthenticatedPostBack() === FALSE) {
            // Format trusted domains as a string
            $TrustedDomains = GetValue('Garden.TrustedDomains', $ConfigurationModel->Data);
            if (is_array($TrustedDomains)) {
                $TrustedDomains = implode("\n", $TrustedDomains);
            }

            $ConfigurationModel->Data['Garden.TrustedDomains'] = $TrustedDomains;

            // Apply the config settings to the form.
            $this->Form->SetData($ConfigurationModel->Data);
        } else {
            // Format the trusted domains as an array based on newlines & spaces
            $TrustedDomains = $this->Form->GetValue('Garden.TrustedDomains');
            $TrustedDomains = explode("\n", $TrustedDomains);
            $TrustedDomains = array_unique(array_filter(array_map('trim', $TrustedDomains)));
            $TrustedDomains = implode("\n", $TrustedDomains);
            $this->Form->SetFormValue('Garden.TrustedDomains', $TrustedDomains);
            if ($this->Form->Save() !== FALSE) {
                $this->InformMessage(T("Your settings have been saved."));
            }

            // Reformat array as string so it displays properly in the form
            $this->Form->SetFormValue('Garden.TrustedDomains', $TrustedDomains);
        }

        $this->Permission('Garden.Settings.Manage');
        $this->Render();
    }

    /**
     * Handle toggling this version of embedding on and off. Take care of disabling the other version of embed (the old plugin).
     *
     * @param string $Toggle
     * @param string $TransientKey
     * @return boolean
     * @throws Gdn_UserException
     */
    private function Toggle($Toggle = '', $TransientKey = '') {
        if (in_array($Toggle, array('enable', 'disable')) && Gdn::Session()->ValidateTransientKey($TransientKey)) {
            if ($Toggle == 'enable' && array_key_exists('embedvanilla', Gdn::PluginManager()->EnabledPlugins()))
                throw new Gdn_UserException('You must disable the "Embed Vanilla" plugin before continuing.');

            // Do the toggle
            SaveToConfig('Garden.Embed.Allow', $Toggle == 'enable' ? TRUE : FALSE);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Allow for a custom embed theme.
     *
     * @since 2.0.18
     * @access public
     */
    public function Theme() {
        // Do nothing by default
    }

}
