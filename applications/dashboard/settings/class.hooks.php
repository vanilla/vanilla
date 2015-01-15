<?php if (!defined('APPLICATION')) exit();
/**
 * Dashboard Application Hooks
 *
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
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

      if ($Session->IsValid()) {
         $ConfirmEmail = C('Garden.Registration.ConfirmEmail', false);
         $Confirmed = GetValue('Confirmed', Gdn::Session()->User, true);

         if ($ConfirmEmail && !$Confirmed) {
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
         $MessageData = $MessageModel->GetMessagesForLocation($Location, $Exceptions, $Sender->Data('Category.CategoryID'));
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
         if (!IsSearchEngine() && !IsMobile() && strtolower($Sender->ControllerName) != 'entry') {
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
		$ForceNoMobile = val('X-UA-Device-Force', $_COOKIE);
		if ($ForceNoMobile === 'desktop') {
		   $Sender->AddAsset('Foot', Wrap(Anchor(T('Back to Mobile Site'), '/profile/nomobile/1'), 'div'), 'MobileLink');
      }

      // Allow global translation of TagHint
      $Sender->AddDefinition("TagHint", T("TagHint", "Start to type..."));
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

      $Menu->AddLink('Appearance', T('Mobile Themes'), '/dashboard/settings/mobilethemes', 'Garden.Settings.Manage');
      if ($MobileThemeOptionsName = C('Garden.MobileThemeOptions.Name'))
         $Menu->AddLink('Appearance', T('Mobile Theme Options'), '/dashboard/settings/mobilethemeoptions', 'Garden.Settings.Manage');


		$Menu->AddLink('Appearance', T('Messages'), '/dashboard/message', 'Garden.Messages.Manage');

      $Menu->AddItem('Users', T('Users'), FALSE, array('class' => 'Users'));
      $Menu->AddLink('Users', T('Users'), '/dashboard/user', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
		$Menu->AddLink('Users', T('Roles & Permissions'), 'dashboard/role', 'Garden.Settings.Manage');

      $Menu->AddLink('Users', T('Registration'), 'dashboard/settings/registration', 'Garden.Settings.Manage');
		$Menu->AddLink('Users', T('Authentication'), 'dashboard/authentication', 'Garden.Settings.Manage');

      if (C('Garden.Registration.Method') == 'Approval')
         $Menu->AddLink('Users', T('Applicants').' <span class="Popin" rel="/dashboard/user/applicantcount"></span>', 'dashboard/user/applicants', 'Garden.Users.Approve');

      $Menu->AddItem('Moderation', T('Moderation'), FALSE, array('class' => 'Moderation'));

      if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage'), FALSE))
         $Menu->AddLink('Moderation', T('Spam Queue'), 'dashboard/log/spam');
      if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'), FALSE))
         $Menu->AddLink('Moderation', T('Moderation Queue').' <span class="Popin" rel="/dashboard/log/count/moderate"></span>', 'dashboard/log/moderation');
      $Menu->AddLink('Moderation', T('Change Log'), 'dashboard/log/edits', 'Garden.Moderation.Manage');
      $Menu->AddLink('Moderation', T('Banning'), 'dashboard/settings/bans', 'Garden.Settings.Manage');

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
      safeHeader('P3P: CP="CAO PSA OUR"', true);

      if ($SSO = Gdn::Request()->Get('sso')) {
         SaveToConfig('Garden.Registration.SendConnectEmail', false, false);

         $IsApi = preg_match('`\.json$`i', Gdn::Request()->Path());

         $UserID = false;
         try {
            $CurrentUserID = Gdn::Session()->UserID;
            $UserID = Gdn::UserModel()->SSO($SSO);
         } catch (Exception $Ex) {
            Trace($Ex, TRACE_ERROR);
         }

         if ($UserID) {
            Gdn::Session()->Start($UserID, !$IsApi, !$IsApi);
            if ($IsApi) {
               Gdn::Session()->ValidateTransientKey(true);
            }

            if ($UserID != $CurrentUserID) {
               Gdn::UserModel()->FireEvent('AfterSignIn');
            }
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
      if (!$Target) {
         $Target = $Sender->Request->Get('redirect');
         if (!$Target)
            $Target = '/';
      }

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

   public function SiteNavModule_all_handler($sender) {
      // Add a link to the community home.
      $sender->addLink('main.home', array('text' => t('Community Home'), 'url' => '/', 'icon' => icon('home'), 'sort' => -100));

      $sender->addGroup('etc', array('sort' => 100));
      if (Gdn::Session()->IsValid()) {
         // Switch between the full site and mobile.
         if (IsMobile()) {
            $sender->addLink('etc.nomobile', array('text' => t('Full Site'), 'url' => '/profile/nomobile', 'icon' => icon('resize-full'), 'sort' => 100));
         }

         $sender->addLink('etc.signout', array('text' => t('Sign Out'), 'url' => SignOutUrl(), 'icon' => icon('signout'), 'sort' => 100));
      } else {
         $sender->addLink('etc.signin', array('text' => t('Sign In'), 'url' => SignInUrl(), 'icon' => icon('signin'), 'sort' => 100));
      }
   }

   /**
    * @param SiteNavModule $sender
    */
   public function SiteNavModule_default_handler($sender) {
      if (Gdn::Session()->IsValid())
         $sender->addLink('main.profile', array('text' => t('Profile'), 'url' => '/profile', 'icon' => icon('user'), 'sort' => 10));
      $sender->addLink('main.activity', array('text' => t('Activity'), 'url' => '/activity', 'icon' => icon('time'), 'sort' => 10));

      // Add the moderation items.
      $sender->addGroup('moderation', array('text' => t('Moderation'), 'sort' => 90));
      if (Gdn::Session()->CheckPermission('Garden.Users.Approve')) {
         $RoleModel = new RoleModel();
         $applicant_count = (int)$RoleModel->GetApplicantCount();
         if ($applicant_count > 0 || true) {
            $sender->addLink('moderation.applicants', array('text' => t('Applicants'), 'url' => '/user/applicants', 'icon' => icon('user'), 'badge' => countString($applicant_count)));
         }
      }

      if (Gdn::Session()->CheckPermission('Garden.Modertion.Manage')) {
         $sender->addLink('moderation.spam', array('text' => 'Spam Queue', 'url' => '/log/spam', 'icon' => icon('spam')));
//         $sender->addLink('moderation.queue', array('text' => 'Moderaton Queue', 'url' => '/log/moderation', 'icon' => icon('report')));
      }

      if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
         $sender->addLink('etc.dashboard', array('text' => t('Dashboard'), 'url' => '/settings', 'icon' => icon('dashboard')));
      }
   }

   /**
    * @param SiteNavModule $sender
    */
   public function SiteNavModule_editprofile_handler($sender) {
      $user = Gdn::Controller()->Data('Profile');
      $user_id = val('UserID', $user);
      $is_me = $user_id == Gdn::Session()->UserID;

      if (!Gdn::Session()->IsValid())
         return;

      // Users can edit their own profiles and moderators can edit any profile.
      if (hasEditProfile($user_id)) {
         $sender->addLink('main.editprofile', array('text' => t('Profile'), 'url' => UserUrl($user, '', 'edit'), 'icon' => icon('edit')));
      }

      if (CheckPermission('Garden.Users.Edit'))
         $sender->addLink('main.editaccount', array('text' => t('Edit Account'), 'url' => "/user/edit/$user_id", 'icon' => icon('cog'), 'class' => 'Popup'));

      $sender->addLink('main.profile', array('text' => t('Back to Profile'), 'url' => UserUrl('user'), 'icon' => icon('arrow-left'), 'sort' => 100));
   }

   /**
    * @param SiteNavModule $sender
    */
   public function SiteNavModule_profile_handler($sender) {
      $user = Gdn::Controller()->Data('Profile');
      $user_id = val('UserID', $user);

      // Show the activity.
      if (C('Garden.Profile.ShowActivities', TRUE)) {
         $sender->addLink('main.activity', array('text' => t('Activity'), 'url' => UserUrl($user, '', 'activity'), 'icon' => icon('time')));
      }

      // Display the notifications for the current user.
      if (Gdn::Controller()->Data('Profile.UserID') == Gdn::Session()->UserID) {
         $sender->addLink('main.notifications', array('text' => t('Notifications'), 'url' => UserUrl($user, '', 'notifications'), 'icon' => icon('globe'), 'badge' => Gdn::Controller()->Data('Profile.CountNotifications')));
      }

      // Show the invitations if we're using the invite registration method.
      if (strcasecmp(C('Garden.Registration.Method'), 'invitation') === 0)
         $sender->addLink('main.invitations', array('text' => t('Invitations'), 'url' => UserUrl($user, '', 'invitations'), 'icon' => icon('ticket')));

      // Users can edit their own profiles and moderators can edit any profile.
      if (hasEditProfile($user_id)) {
         $sender->addLink('main.editprofile', array('text' => t('Edit Profile'), 'url' => UserUrl($user, '', 'edit'), 'icon' => icon('edit')));
      }

      // Add a stub group for moderation.
      $sender->addGroup('moderation', array('text' => t('Moderation'), 'sort' => 90));
   }
}
