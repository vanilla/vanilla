<?php
/**
 * Managing site statistic reporting.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /statistics endpoint.
 */
class StatisticsController extends DashboardController {

    /** @var array Models to automatically instantiate. */
    public $Uses = ['Form'];

    /**
     * Output available info.
     */
    public function info() {
        $this->permission('Garden.Settings.Manage');
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
        $this->setHighlightRoute('dashboard/statistics');
        //$this->addJsFile('statistics.js');
        $this->title(t('Vanilla Statistics'));

        if ($this->Form->authenticatedPostBack()) {
            $flow = true;

            if ($flow && $this->Form->getFormValue('Reregister')) {
                $id = Gdn::installationID();
                $secret = Gdn::installationSecret();
                Gdn::installationID(false);
                Gdn::installationSecret(false);

                Gdn::statistics()->register();

                if (!Gdn::installationID()) {
                    Gdn::installationID($id);
                    Gdn::installationSecret($secret);
                }
                $this->Form->setFormValue('InstallationID', Gdn::installationID());
                $this->Form->setFormValue('InstallationSecret', Gdn::installationSecret());
            }

            if ($flow && $this->Form->getFormValue('Save')) {
                Gdn::installationID($this->Form->getFormValue('InstallationID'));
                Gdn::installationSecret($this->Form->getFormValue('InstallationSecret'));
                $this->informMessage(t("Your settings have been saved."));
            }

            if ($flow && $this->Form->getFormValue('AllowLocal')) {
                saveToConfig('Garden.Analytics.AllowLocal', true);
            }

            if ($flow && $this->Form->getFormValue('Allow')) {
                saveToConfig('Garden.Analytics.Enabled', true);
            }

            if ($flow && $this->Form->getFormValue('ClearCredentials')) {
                Gdn::installationID(false);
                Gdn::installationSecret(false);
                Gdn::statistics()->tick();
                $flow = false;
            }
        } else {
            $this->Form->setValue('InstallationID', Gdn::installationID());
            $this->Form->setValue('InstallationSecret', Gdn::installationSecret());
        }

        $analyticsEnabled = Gdn_Statistics::checkIsEnabled();
        if ($analyticsEnabled) {
            $confFile = Gdn::config()->defaultPath();
            $this->setData('ConfWritable', $confWritable = is_writable($confFile));
            if (!$confWritable) {
                $analyticsEnabled = false;
            }

            $this->Form->setFormValue('InstallationID', Gdn::installationID());
            $this->Form->setFormValue('InstallationSecret', Gdn::installationSecret());
        }

        $this->setData('AnalyticsEnabled', $analyticsEnabled);

        $notifyMessage = Gdn::get('Garden.Analytics.Notify', false);
        $this->setData('NotifyMessage', $notifyMessage);
        if ($notifyMessage !== false) {
            Gdn::set('Garden.Analytics.Notify', null);
        }

        $this->setData(
            'FormView',
            $this->data('AnalyticsEnabled') ? 'configuration' : 'disabled'
        );

        $this->render(
            $this->deliveryType() === DELIVERY_TYPE_VIEW ? $this->data('FormView') : ''
        );
    }

    /**
     * Verify connection credentials.
     *
     * @since 2.0.17
     * @access public
     */
    public function verify() {
        $this->permission('Garden.Settings.Manage');
        $credentialsValid = Gdn::statistics()->validateCredentials();
        $this->setData('StatisticsVerified', $credentialsValid);
        $this->render();
    }
}
