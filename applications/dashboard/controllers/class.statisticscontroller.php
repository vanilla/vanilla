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
    public function Info() {
        $this->SetData('FirstDate', Gdn::Statistics()->FirstDate());
        $this->Render();
    }

    /**
     * Highlight menu path. Automatically run on every use.
     *
     * @since 2.0.17
     * @access public
     */
    public function Initialize() {
        parent::Initialize();
        Gdn_Theme::Section('Dashboard');
        if ($this->Menu)
            $this->Menu->HighlightRoute('/dashboard/settings');
    }

    /**
     * Statistics setup & configuration.
     *
     * @since 2.0.17
     * @access public
     */
    public function Index() {
        $this->Permission('Garden.Settings.Manage');
        $this->AddSideMenu('dashboard/statistics');
        //$this->AddJsFile('statistics.js');
        $this->Title(T('Vanilla Statistics'));
        $this->EnableSlicing($this);

        if ($this->Form->IsPostBack()) {
            $Flow = TRUE;

            if ($Flow && $this->Form->GetFormValue('Reregister')) {
                Gdn::Statistics()->Register();
            }

            if ($Flow && $this->Form->GetFormValue('Save')) {
                Gdn::InstallationID($this->Form->GetFormValue('InstallationID'));
                Gdn::InstallationSecret($this->Form->GetFormValue('InstallationSecret'));
                $this->InformMessage(T("Your settings have been saved."));
            }

            if ($Flow && $this->Form->GetFormValue('AllowLocal')) {
                SaveToConfig('Garden.Analytics.AllowLocal', TRUE);
            }

            if ($Flow && $this->Form->GetFormValue('Allow')) {
                SaveToConfig('Garden.Analytics.Enabled', TRUE);
            }

            if ($Flow && $this->Form->GetFormValue('ClearCredentials')) {
                Gdn::InstallationID(FALSE);
                Gdn::InstallationSecret(FALSE);
                Gdn::Statistics()->Tick();
                $Flow = FALSE;
            }
        } else {
            $this->Form->SetValue('InstallationID', Gdn::InstallationID());
            $this->Form->SetValue('InstallationSecret', Gdn::InstallationSecret());
        }

        $AnalyticsEnabled = Gdn_Statistics::CheckIsEnabled();
        if ($AnalyticsEnabled) {
            $ConfFile = Gdn::Config()->DefaultPath();
            $this->SetData('ConfWritable', $ConfWritable = is_writable($ConfFile));
            if (!$ConfWritable)
                $AnalyticsEnabled = FALSE;
        }

        $this->SetData('AnalyticsEnabled', $AnalyticsEnabled);

        $NotifyMessage = Gdn::Get('Garden.Analytics.Notify', FALSE);
        $this->SetData('NotifyMessage', $NotifyMessage);
        if ($NotifyMessage !== FALSE)
            Gdn::Set('Garden.Analytics.Notify', NULL);

        $this->Render();
    }

    /**
     * Verify connection credentials.
     *
     * @since 2.0.17
     * @access public
     */
    public function Verify() {
        $CredentialsValid = Gdn::Statistics()->ValidateCredentials();
        $this->SetData('StatisticsVerified', $CredentialsValid);
        $this->Render();
    }

}
