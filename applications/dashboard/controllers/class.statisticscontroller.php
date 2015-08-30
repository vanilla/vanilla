<?php
/**
 * Managing site statistic reporting.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /statistics endpoint.
 */
class StatisticsController extends DashboardController {

    /** @var array Models to automatically instantiate. */
    public $Uses = array('Form');

    /**
     * Output available info.
     */
    public function info() {
        $this->setData('FirstDate', Gdn::statistics()->firstDate());
        $this->render();
    }

    /**
     * Highlight menu path. Automatically run on every use.
     *
     * @since 2.0.17
     * @access public
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        if ($this->Menu) {
            $this->Menu->highlightRoute('/dashboard/settings');
        }
    }

    /**
     * Statistics setup & configuration.
     *
     * @since 2.0.17
     * @access public
     */
    public function index() {
        $this->permission('Garden.Settings.Manage');
        $this->addSideMenu('dashboard/statistics');
        //$this->addJsFile('statistics.js');
        $this->title(t('Vanilla Statistics'));
        $this->enableSlicing($this);

        if ($this->Form->isPostBack()) {
            $Flow = true;

            if ($Flow && $this->Form->getFormValue('Reregister')) {
                Gdn::Statistics()->register();
            }

            if ($Flow && $this->Form->getFormValue('Save')) {
                Gdn::installationID($this->Form->getFormValue('InstallationID'));
                Gdn::installationSecret($this->Form->getFormValue('InstallationSecret'));
                $this->informMessage(t("Your settings have been saved."));
            }

            if ($Flow && $this->Form->getFormValue('AllowLocal')) {
                saveToConfig('Garden.Analytics.AllowLocal', true);
            }

            if ($Flow && $this->Form->getFormValue('Allow')) {
                saveToConfig('Garden.Analytics.Enabled', true);
            }

            if ($Flow && $this->Form->getFormValue('ClearCredentials')) {
                Gdn::installationID(false);
                Gdn::installationSecret(false);
                Gdn::statistics()->Tick();
                $Flow = false;
            }
        } else {
            $this->Form->setValue('InstallationID', Gdn::installationID());
            $this->Form->setValue('InstallationSecret', Gdn::installationSecret());
        }

        $AnalyticsEnabled = Gdn_Statistics::checkIsEnabled();
        if ($AnalyticsEnabled) {
            $ConfFile = Gdn::config()->defaultPath();
            $this->setData('ConfWritable', $ConfWritable = is_writable($ConfFile));
            if (!$ConfWritable) {
                $AnalyticsEnabled = false;
            }
        }

        $this->setData('AnalyticsEnabled', $AnalyticsEnabled);

        $NotifyMessage = Gdn::get('Garden.Analytics.Notify', false);
        $this->setData('NotifyMessage', $NotifyMessage);
        if ($NotifyMessage !== false) {
            Gdn::set('Garden.Analytics.Notify', null);
        }

        $this->render();
    }

    /**
     * Verify connection credentials.
     *
     * @since 2.0.17
     * @access public
     */
    public function verify() {
        $CredentialsValid = Gdn::statistics()->validateCredentials();
        $this->setData('StatisticsVerified', $CredentialsValid);
        $this->render();
    }
}
