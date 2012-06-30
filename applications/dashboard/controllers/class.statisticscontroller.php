<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Statistics Controller
 *
 * @package Dashboard
 */
 
/**
 * Managing site statistic reporting.
 *
 * @since 2.0.17
 * @package Dashboard
 */
class StatisticsController extends DashboardController {
   /** @var array Models to automatically instantiate. */
   public $Uses = array('Form');
   
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
      }
      
      $AnalyticsEnabled = Gdn_Statistics::CheckIsEnabled();
      if ($AnalyticsEnabled) {
         $ConfFile = PATH_CONF.'/config.php';
         $this->SetData('ConfWritable', $ConfWritable = is_writable($ConfFile));
         if (!$ConfWritable)
            $AnalyticsEnabled = FALSE;
      }
      
      $this->SetData('AnalyticsEnabled', $AnalyticsEnabled);
      
      $NotifyMessage = Gdn::Get('Garden.Analytics.Notify', FALSE);
      $this->SetData('NotifyMessage', $NotifyMessage);
      if ($NotifyMessage !== FALSE)
         Gdn::Set('Garden.Analytics.Notify', NULL);
      
      $this->Form->SetFormValue('InstallationID', Gdn::InstallationID());
      $this->Form->SetFormValue('InstallationSecret', Gdn::InstallationSecret());
      
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