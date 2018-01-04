<?php
/**
 * Manages the embedding of a forum on a foreign page.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0.18
 */

/**
 * Handles /embed endpoint.
 */
class EmbedController extends DashboardController {

    /** @var array Models to include. */
    public $Uses = ['Database', 'Form'];

    /**
     * Default method.
     */
    public function index() {
        redirectTo('embed/comments');
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
    public function comments($toggle = '', $transientKey = '') {
        $this->settings($toggle, $transientKey);
    }

    /**
     * Embed the entire forum.
     *
     * @param string $toggle
     * @param string $transientKey
     */
    public function forum($toggle = '', $transientKey = '') {
        $this->permission('Garden.Settings.Manage');

        try {
            if ($this->toggle($toggle, $transientKey)) {
                redirectTo('embed/forum');
            }
        } catch (Gdn_UserException $ex) {
            $this->Form->addError($ex);
        }

        $this->setHighlightRoute('embed/forum');
        $this->title('Embed Forum');
        $this->render();
    }

    /**
     * Options page.
     *
     * @param string $toggle
     * @param string $transientKey
     */
    public function advanced($toggle = '', $transientKey = '') {
        $this->settings($toggle, $transientKey);
    }

    public function settings($toggle = '', $transientKey = '') {
        $this->permission('Garden.Settings.Manage');

//        try {
//            if ($this->toggle($Toggle, $TransientKey)) {
//                redirectTo('embed/advanced');
//            }
//        } catch (Gdn_UserException $Ex) {
//            $this->Form->addError($Ex);
//        }

        $this->setHighlightRoute('embed/forum');
        $this->Form = new Gdn_Form();

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(
            [
                'Garden.Embed.RemoteUrl',
                'Garden.Embed.ForceDashboard',
                'Garden.Embed.ForceForum',
                'Garden.Embed.ForceMobile',
                'Garden.SignIn.Popup',
                'Garden.Embed.CommentsPerPage',
                'Garden.Embed.SortComments',
                'Garden.Embed.PageToForum'
            ]
        );

        $this->Form->setModel($configurationModel);
        if ($this->Form->authenticatedPostBack()) {
            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
            }
        } else {
            // Apply the config settings to the form.
            $this->Form->setData($configurationModel->Data);
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
     * @param string $toggle
     * @param string $transientKey
     * @return boolean
     * @throws Gdn_UserException
     */
    private function toggle($toggle = '', $transientKey = '') {
        if (in_array($toggle, ['enable', 'disable']) && Gdn::session()->validateTransientKey($transientKey)) {
            if ($toggle == 'enable' && Gdn::addonManager()->isEnabled('embedvanilla', \Vanilla\Addon::TYPE_ADDON)) {
                throw new Gdn_UserException('You must disable the "Embed Vanilla" plugin before continuing.');
            }

            // Do the toggle
            saveToConfig('Garden.Embed.Allow', $toggle == 'enable' ? true : false);
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
