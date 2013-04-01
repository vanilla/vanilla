<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class DashboardHooks implements Gdn_IPlugin {
   public function Setup() {
      return TRUE;
   }

   /**
    *
    * @param Gdn_Controller $Sender
    */
   public function Base_Render_Before($Sender) {
      $Session = Gdn::Session();

      // Enable theme previewing
      if ($Session->IsValid()) {
         $PreviewThemeName = $Session->GetPreference('PreviewThemeName', '');
			$PreviewThemeFolder = $Session->GetPreference('PreviewThemeFolder', '');
         if ($PreviewThemeName != '') {
            $Sender->Theme = $PreviewThemeName;
				$Sender->InformMessage(
					sprintf(T('You are previewing the %s theme.'), Wrap($PreviewThemeName, 'em'))
						.'<div class="PreviewThemeButtons">'
						.Anchor(T('Apply'), 'settings/themes/'.$PreviewThemeName.'/'.$Session->TransientKey(), 'PreviewThemeButton')
						.' '.Anchor(T('Cancel'), 'settings/cancelpreview/', 'PreviewThemeButton')
						.'</div>',
					'DoNotDismiss'
				);
         }
      }

      if ($Session->IsValid() && $EmailKey = Gdn::Session()->GetAttribute('EmailKey')) {
         $NotifyEmailConfirm = TRUE;
         
         // If this user was manually moved out of the confirmation role, get rid of their 'awaiting confirmation' flag
         $ConfirmEmailRole = C('Garden.Registration.ConfirmEmailRole', FALSE);
         
         $UserRoles = array();
         $RoleData = Gdn::UserModel()->GetRoles($Session->UserID);
         if ($RoleData !== FALSE && $RoleData->NumRows() > 0) 
            $UserRoles = ConsolidateArrayValuesByKey($RoleData->Result(DATASET_TYPE_ARRAY), 'RoleID','Name');
         
         if ($ConfirmEmailRole !== FALSE && !array_key_exists($ConfirmEmailRole, $UserRoles)) {
            Gdn::UserModel()->SaveAttribute($Session->UserID, "EmailKey", NULL);
            $NotifyEmailConfirm = FALSE;
         }
         
         if ($NotifyEmailConfirm) {
            $Message = FormatString(T('You need to confirm your email address.', 'You need to confirm your email address. Click <a href="{/entry/emailconfirmrequest,url}">here</a> to resend the confirmation email.'));
            $Sender->InformMessage($Message, '');
         }
      }

      // Add Message Modules (if necessary)
      $MessageCache = Gdn::Config('Garden.Messages.Cache', array());
      $Location = $Sender->Application.'/'.substr($Sender->ControllerName, 0, -10).'/'.$Sender->RequestMethod;
		$Exceptions = array('[Base]');
// 2011-09-09 - mosullivan - No longer allowing messages in dashboard
//		if ($Sender->MasterView == 'admin')
//			$Exceptions[] = '[Admin]';
//		else if (in_array($Sender->MasterView, array('', 'default')))
		if (in_array($Sender->MasterView, array('', 'default')))
			$Exceptions[] = '[NonAdmin]';
      
      // SignIn popup is a special case
      $SignInOnly = ($Sender->DeliveryType() == DELIVERY_TYPE_VIEW && $Location == 'Dashboard/entry/signin');
      if ($SignInOnly)
         $Exceptions = array();
			
		if ($Sender->MasterView != 'admin' && !$Sender->Data('_NoMessages') && (GetValue('MessagesLoaded', $Sender) != '1' && $Sender->MasterView != 'empty' && ArrayInArray($Exceptions, $MessageCache, FALSE) || InArrayI($Location, $MessageCache))) {
         $MessageModel = new MessageModel();
         $MessageData = $MessageModel->GetMessagesForLocation($Location, $Exceptions);
         foreach ($MessageData as $Message) {
            $MessageModule = new MessageModule($Sender, $Message);
            if ($SignInOnly) // Insert special messages even in SignIn popup
               echo $MessageModule;
            elseif ($Sender->DeliveryType() == DELIVERY_TYPE_ALL)
               $Sender->AddModule($MessageModule);
         }
			$Sender->MessagesLoaded = '1'; // Fixes a bug where render gets called more than once and messages are loaded/displayed redundantly.
      }
		
      if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
         $Gdn_Statistics = Gdn::Factory('Statistics');
         $Gdn_Statistics->Check($Sender);
      }
		
      // Allow forum embedding
      if ($Embed = C('Garden.Embed.Allow')) {
         // Record the remote url where the forum is being embedded.
         $RemoteUrl = C('Garden.Embed.RemoteUrl');
         if (!$RemoteUrl) {
            $RemoteUrl = GetIncomingValue('remote');
            if ($RemoteUrl)
               SaveToConfig('Garden.Embed.RemoteUrl', $RemoteUrl);
         }
         if ($RemoteUrl)
            $Sender->AddDefinition('RemoteUrl', $RemoteUrl);

         // Force embedding?
         if (!IsSearchEngine() && !IsMobile()) {
            $Sender->AddDefinition('ForceEmbedForum', C('Garden.Embed.ForceForum') ? '1' : '0');
            $Sender->AddDefinition('ForceEmbedDashboard', C('Garden.Embed.ForceDashboard') ? '1' : '0');
         }

         $Sender->AddDefinition('Path', Gdn::Request()->Path());
         // $Sender->AddDefinition('MasterView', $Sender->MasterView);
         $Sender->AddDefinition('InDashboard', $Sender->MasterView == 'admin' ? '1' : '0');
         
         if ($Embed === 2)
            $Sender->AddJsFile('vanilla.embed.local.js');
         else
            $Sender->AddJsFile('embed_local.js');
      } else {
         $Sender->SetHeader('X-Frame-Options', 'SAMEORIGIN');
      }
         
      // Allow return to mobile site
		$ForceNoMobile = Gdn_CookieIdentity::GetCookiePayload('VanillaNoMobile');
		if ($ForceNoMobile !== FALSE && is_array($ForceNoMobile) && in_array('force', $ForceNoMobile))
		   $Sender->AddAsset('Foot', Wrap(Anchor(T('Back to Mobile Site'), '/profile/nomobile/1'), 'div'), 'MobileLink');
   }
   
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Dashboard', T('Dashboard'), FALSE, array('class' => 'Dashboard'));
      $Menu->AddLink('Dashboard', T('Dashboard'), '/dashboard/settings', 'Garden.Moderation.Manage');
		$Menu->AddLink('Dashboard', T('Getting Started'), '/dashboard/settings/gettingstarted', 'Garden.Settings.Manage');
		$Menu->AddLink('Dashboard', T('Help &amp; Tutorials'), '/dashboard/settings/tutorials', 'Garden.Settings.Manage');

      $Menu->AddItem('Appearance', T('Appearance'), FALSE, array('class' => 'Appearance'));
		$Menu->AddLink('Appearance', T('Banner'), '/dashboard/settings/banner', 'Garden.Settings.Manage');
      $Menu->AddLink('Appearance', T('Homepage'), '/dashboard/settings/homepage', 'Garden.Settings.Manage');
      $Menu->AddLink('Appearance', T('Themes'), '/dashboard/settings/themes', 'Garden.Settings.Manage');
      if ($ThemeOptionsName = C('Garden.ThemeOptions.Name')) 
         $Menu->AddLink('Appearance', T('Theme Options'), '/dashboard/settings/themeoptions', 'Garden.Settings.Manage');

		$Menu->AddLink('Appearance', T('Messages'), '/dashboard/message', 'Garden.Settings.Manage');

      $Menu->AddItem('Users', T('Users'), FALSE, array('class' => 'Users'));
      $Menu->AddLink('Users', T('Users'), '/dashboard/user', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
		$Menu->AddLink('Users', T('Roles & Permissions'), 'dashboard/role', 'Garden.Settings.Manage');
			
      $Menu->AddLink('Users', T('Registration'), 'dashboard/settings/registration', 'Garden.Settings.Manage');
		$Menu->AddLink('Users', T('Authentication'), 'dashboard/authentication', 'Garden.Settings.Manage');
			
      if (C('Garden.Registration.Method') == 'Approval')
         $Menu->AddLink('Users', T('Applicants').' <span class="Popin" rel="/dashboard/user/applicantcount"></span>', 'dashboard/user/applicants', 'Garden.Users.Approve');

      $Menu->AddItem('Moderation', T('Moderation'), FALSE, array('class' => 'Moderation'));
      $Menu->AddLink('Moderation', T('Spam Queue'), 'dashboard/log/spam', 'Garden.Moderation.Manage');
      $Menu->AddLink('Moderation', T('Moderation Queue').' <span class="Popin" rel="/dashboard/log/count/moderate"></span>', 'dashboard/log/moderation', 'Garden.Moderation.Manage');
      $Menu->AddLink('Moderation', T('Change Log'), 'dashboard/log/edits', 'Garden.Moderation.Manage');
      $Menu->AddLink('Moderation', T('Banning'), 'dashboard/settings/bans', 'Garden.Moderation.Manage');
		
		$Menu->AddItem('Forum', T('Forum Settings'), FALSE, array('class' => 'Forum'));
      $Menu->AddLink('Forum', T('Social'), 'dashboard/social', 'Garden.Settings.Manage');
		
		$Menu->AddItem('Reputation', T('Reputation'), FALSE, array('class' => 'Reputation'));
		
		$Menu->AddItem('Add-ons', T('Addons'), FALSE, array('class' => 'Addons'));
      $Menu->AddLink('Add-ons', T('Plugins'), 'dashboard/settings/plugins', 'Garden.Settings.Manage');
      $Menu->AddLink('Add-ons', T('Applications'), 'dashboard/settings/applications', 'Garden.Settings.Manage');
      $Menu->AddLink('Add-ons', T('Locales'), 'dashboard/settings/locales', 'Garden.Settings.Manage');

      $Menu->AddItem('Site Settings', T('Settings'), FALSE, array('class' => 'SiteSettings'));
      $Menu->AddLink('Site Settings', T('Outgoing Email'), 'dashboard/settings/email', 'Garden.Settings.Manage');
      $Menu->AddLink('Site Settings', T('Routes'), 'dashboard/routes', 'Garden.Settings.Manage');
      $Menu->AddLink('Site Settings', T('Statistics'), 'dashboard/statistics', 'Garden.Settings.Manage');
		
		$Menu->AddItem('Import', T('Import'), FALSE, array('class' => 'Import'));
		$Menu->AddLink('Import', FALSE, 'dashboard/import', 'Garden.Settings.Manage');
   }
   
   /**
    * Set P3P header because IE won't allow cookies thru the iFrame without it.
    *
    * This must be done in the Dispatcher because of PrivateCommunity.
    * That precludes using Controller->SetHeader.
    * This is done so comment & forum embedding can work in old IE.
    */
   public function Gdn_Dispatcher_AppStartup_Handler($Sender) {
      header('P3P: CP="CAO PSA OUR"', TRUE);
      
      if (!Gdn::Session()->IsValid() && $SSO = Gdn::Request()->Get('sso')) {
         SaveToConfig('Garden.Registration.SendConnectEmail', FALSE, FALSE);
         
         $UserID = FALSE;
         try {
            $UserID = Gdn::UserModel()->SSO($SSO);
         } catch (Exception $Ex) {
            Trace($Ex, TRACE_ERROR);
         }
         
         if ($UserID) {
            Gdn::Session()->Start($UserID, TRUE, TRUE);
            Gdn::UserModel()->FireEvent('AfterSignIn');
         } else {
            // There was some sort of error. Let's print that out.
            Trace(Gdn::UserModel()->Validation->ResultsText(), TRACE_WARNING);
         }
      }
   }
   
   /**
    * Method for plugins that want a friendly /sso method to hook into.
    * 
    * @param RootController $Sender
    * @param string $Target The url to redirect to after sso.
    */
   public function RootController_SSO_Create($Sender, $Target = '') {
      if (!$Target)
         $Target = '/';
      
      // TODO: Make sure the target is a safe redirect.
      
      // Get the default authentication provider.
      $DefaultProvider = Gdn_AuthenticationProviderModel::GetDefault();
      $Sender->EventArguments['Target'] = $Target;
      $Sender->EventArguments['DefaultProvider'] = $DefaultProvider;
      $Handled = FALSE;
      $Sender->EventArguments['Handled'] =& $Handled;
      
      $Sender->FireEvent('SSO');
      
      // If an event handler didn't handle the signin then just redirect to the target.
      if (!$Handled)
         Redirect($Target, 302);
   }
}