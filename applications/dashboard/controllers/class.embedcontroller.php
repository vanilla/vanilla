<?php
/**
 * Manages the embedding of a forum on a foreign page.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
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
        $this->settings($Toggle, $TransientKey);
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

        $this->setHighlightRoute('embed/forum');
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
        $this->settings($Toggle, $TransientKey);
    }

    public function settings($Toggle = '', $TransientKey = '') {
        $this->permission('Garden.Settings.Manage');

//        try {
//            if ($this->toggle($Toggle, $TransientKey)) {
//                redirect('embed/advanced');
//            }
//        } catch (Gdn_UserException $Ex) {
//            $this->Form->addError($Ex);
//        }

        $this->setHighlightRoute('embed/forum');
        $this->Form = new Gdn_Form();

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(
            array(
                'Garden.Embed.RemoteUrl',
                'Garden.Embed.ForceDashboard',
                'Garden.Embed.ForceForum',
                'Garden.Embed.ForceMobile',
                'Garden.SignIn.Popup',
                'Garden.Embed.CommentsPerPage',
                'Garden.Embed.SortComments',
                'Garden.Embed.PageToForum'
            )
        );

        $this->Form->setModel($ConfigurationModel);
        if ($this->Form->authenticatedPostBack()) {
            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
            }
        } else {
            // Apply the config settings to the form.
            $this->Form->setData($ConfigurationModel->Data);
        }

        $this->title(t('Embed Settings'));
        $this->render();
    }

    public function wordpress() {
        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('embed/forum');
        $this->render();
    }

    public function universal() {
        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('embed/forum');
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
            if ($Toggle == 'enable' && Gdn::addonManager()->isEnabled('embedvanilla', \Vanilla\Addon::TYPE_ADDON)) {
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
