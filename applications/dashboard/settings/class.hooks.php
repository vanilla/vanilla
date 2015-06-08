<?php
/**
 * DashboardHooks class.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Event handlers for the Dashboard application.
 */
class DashboardHooks implements Gdn_IPlugin {

    /**
     * Do nothing.
     */
    public function setup() {
    }

    /**
     * Fire before every page render.
     *
     * @param Gdn_Controller $Sender
     */
    public function base_Render_Before($Sender) {
        $Session = Gdn::session();

        // Enable theme previewing
        if ($Session->isValid()) {
            $PreviewThemeName = $Session->GetPreference('PreviewThemeName', '');
            $PreviewThemeFolder = $Session->GetPreference('PreviewThemeFolder', '');
            if ($PreviewThemeName != '') {
                $Sender->Theme = $PreviewThemeName;
                $Sender->informMessage(
                    sprintf(t('You are previewing the %s theme.'), wrap($PreviewThemeName, 'em'))
                    .'<div class="PreviewThemeButtons">'
                    .anchor(t('Apply'), 'settings/themes/'.$PreviewThemeName.'/'.$Session->TransientKey(), 'PreviewThemeButton')
                    .' '.anchor(t('Cancel'), 'settings/cancelpreview/', 'PreviewThemeButton')
                    .'</div>',
                    'DoNotDismiss'
                );
            }
        }

        if ($Session->isValid()) {
            $ConfirmEmail = c('Garden.Registration.ConfirmEmail', false);
            $Confirmed = val('Confirmed', Gdn::session()->User, true);

            if ($ConfirmEmail && !$Confirmed) {
                $Message = formatString(t('You need to confirm your email address.', 'You need to confirm your email address. Click <a href="{/entry/emailconfirmrequest,url}">here</a> to resend the confirmation email.'));
                $Sender->informMessage($Message, '');
            }
        }

        // Add Message Modules (if necessary)
        $MessageCache = Gdn::config('Garden.Messages.Cache', array());
        $Location = $Sender->Application.'/'.substr($Sender->ControllerName, 0, -10).'/'.$Sender->RequestMethod;
        $Exceptions = array('[Base]');
// 2011-09-09 - mosullivan - No longer allowing messages in dashboard
//		if ($Sender->MasterView == 'admin')
//			$Exceptions[] = '[Admin]';
//		else if (in_array($Sender->MasterView, array('', 'default')))
        if (in_array($Sender->MasterView, array('', 'default'))) {
            $Exceptions[] = '[NonAdmin]';
        }

        // SignIn popup is a special case
        $SignInOnly = ($Sender->deliveryType() == DELIVERY_TYPE_VIEW && $Location == 'Dashboard/entry/signin');
        if ($SignInOnly) {
            $Exceptions = array();
        }

        if ($Sender->MasterView != 'admin' && !$Sender->data('_NoMessages') && (val('MessagesLoaded', $Sender) != '1' && $Sender->MasterView != 'empty' && ArrayInArray($Exceptions, $MessageCache, false) || InArrayI($Location, $MessageCache))) {
            $MessageModel = new MessageModel();
            $MessageData = $MessageModel->GetMessagesForLocation($Location, $Exceptions, $Sender->data('Category.CategoryID'));
            foreach ($MessageData as $Message) {
                $MessageModule = new MessageModule($Sender, $Message);
                if ($SignInOnly) { // Insert special messages even in SignIn popup
                    echo $MessageModule;
                } elseif ($Sender->deliveryType() == DELIVERY_TYPE_ALL)
                    $Sender->addModule($MessageModule);
            }
            $Sender->MessagesLoaded = '1'; // Fixes a bug where render gets called more than once and messages are loaded/displayed redundantly.
        }

        if ($Sender->deliveryType() == DELIVERY_TYPE_ALL) {
            $Gdn_Statistics = Gdn::Factory('Statistics');
            $Gdn_Statistics->Check($Sender);
        }

        // Allow forum embedding
        if ($Embed = c('Garden.Embed.Allow')) {
            // Record the remote url where the forum is being embedded.
            $RemoteUrl = c('Garden.Embed.RemoteUrl');
            if (!$RemoteUrl) {
                $RemoteUrl = GetIncomingValue('remote');
                if ($RemoteUrl) {
                    saveToConfig('Garden.Embed.RemoteUrl', $RemoteUrl);
                }
            }
            if ($RemoteUrl) {
                $Sender->addDefinition('RemoteUrl', $RemoteUrl);
            }

            // Force embedding?
            if (!IsSearchEngine() && !IsMobile() && strtolower($Sender->ControllerName) != 'entry') {
                $Sender->addDefinition('ForceEmbedForum', c('Garden.Embed.ForceForum') ? '1' : '0');
                $Sender->addDefinition('ForceEmbedDashboard', c('Garden.Embed.ForceDashboard') ? '1' : '0');
            }

            $Sender->addDefinition('Path', Gdn::request()->Path());
            // $Sender->addDefinition('MasterView', $Sender->MasterView);
            $Sender->addDefinition('InDashboard', $Sender->MasterView == 'admin' ? '1' : '0');

            if ($Embed === 2) {
                $Sender->addJsFile('vanilla.embed.local.js');
            } else {
                $Sender->addJsFile('embed_local.js');
            }
        } else {
            $Sender->setHeader('X-Frame-Options', 'SAMEORIGIN');
        }

        // Allow return to mobile site
        $ForceNoMobile = val('X-UA-Device-Force', $_COOKIE);
        if ($ForceNoMobile === 'desktop') {
            $Sender->AddAsset('Foot', wrap(Anchor(t('Back to Mobile Site'), '/profile/nomobile/1'), 'div'), 'MobileLink');
        }

        // Allow global translation of TagHint
        $Sender->addDefinition("TagHint", t("TagHint", "Start to type..."));
    }

    /**
     * @param $Sender
     */
    public function base_GetAppSettingsMenuItems_Handler($Sender) {
        // SideMenuModule menu
        $Menu = &$Sender->EventArguments['SideMenu'];
        $Menu->AddItem('Dashboard', t('Dashboard'), false, array('class' => 'Dashboard'));
        $Menu->addLink('Dashboard', t('Dashboard'), '/dashboard/settings', 'Garden.Settings.View', array('class' => 'nav-dashboard'));
        $Menu->addLink('Dashboard', t('Getting Started'), '/dashboard/settings/gettingstarted', 'Garden.Settings.Manage', array('class' => 'nav-getting-started'));
        $Menu->addLink('Dashboard', t('Help &amp; Tutorials'), '/dashboard/settings/tutorials', 'Garden.Settings.View', array('class' => 'nav-tutorials'));

        $Menu->AddItem('Appearance', t('Appearance'), false, array('class' => 'Appearance'));
        $Menu->addLink('Appearance', t('Banner'), '/dashboard/settings/banner', 'Garden.Community.Manage', array('class' => 'nav-banner'));
        $Menu->addLink('Appearance', t('Homepage'), '/dashboard/settings/homepage', 'Garden.Settings.Manage', array('class' => 'nav-homepage'));

        $Menu->addLink('Appearance', t('Themes'), '/dashboard/settings/themes', 'Garden.Settings.Manage', array('class' => 'nav-themes'));
        if ($ThemeOptionsName = c('Garden.ThemeOptions.Name')) {
            $Menu->addLink('Appearance', t('Theme Options'), '/dashboard/settings/themeoptions', 'Garden.Settings.Manage', array('class' => 'nav-theme-options'));
        }

        $Menu->addLink('Appearance', t('Mobile Themes'), '/dashboard/settings/mobilethemes', 'Garden.Settings.Manage', array('class' => 'nav-mobile-themes'));
        if ($MobileThemeOptionsName = c('Garden.MobileThemeOptions.Name')) {
            $Menu->addLink('Appearance', t('Mobile Theme Options'), '/dashboard/settings/mobilethemeoptions', 'Garden.Settings.Manage', array('class' => 'nav-mobile-theme-options'));
        }


        $Menu->addLink('Appearance', t('Messages'), '/dashboard/message', 'Garden.Community.Manage', array('class' => 'nav-messages'));

        $Menu->AddItem('Users', t('Users'), false, array('class' => 'Users'));
        $Menu->addLink('Users', t('Users'), '/dashboard/user', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'), array('class' => 'nav-users'));

        if (Gdn::session()->checkPermission(array('Garden.Settings.Manage', 'Garden.Roles.Manage'), false)) {
            $Menu->addLink('Users', t('Roles & Permissions'), 'dashboard/role', false, array('class' => 'nav-roles'));
        }

        $Menu->addLink('Users', t('Registration'), 'dashboard/settings/registration', 'Garden.Settings.Manage', array('class' => 'nav-registration'));
        $Menu->addLink('Users', t('Authentication'), 'dashboard/authentication', 'Garden.Settings.Manage', array('class' => 'nav-authentication'));

        if (c('Garden.Registration.Method') == 'Approval') {
            $Menu->addLink('Users', t('Applicants').' <span class="Popin" rel="/dashboard/user/applicantcount"></span>', 'dashboard/user/applicants', 'Garden.Users.Approve', array('class' => 'nav-applicants'));
        }

        $Menu->AddItem('Moderation', t('Moderation'), false, array('class' => 'Moderation'));

        if (Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage'), false)) {
            $Menu->addLink('Moderation', t('Spam Queue'), 'dashboard/log/spam', false, array('class' => 'nav-spam-queue'));
        }
        if (Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'), false)) {
            $Menu->addLink('Moderation', t('Moderation Queue').' <span class="Popin" rel="/dashboard/log/count/moderate"></span>', 'dashboard/log/moderation', false, array('class' => 'nav-moderation-queue'));
        }
        $Menu->addLink('Moderation', t('Change Log'), 'dashboard/log/edits', 'Garden.Moderation.Manage', array('class' => 'nav-change-log'));
        $Menu->addLink('Moderation', t('Banning'), 'dashboard/settings/bans', 'Garden.Community.Manage', array('class' => 'nav-bans'));

        $Menu->AddItem('Forum', t('Forum Settings'), false, array('class' => 'Forum'));
        $Menu->addLink('Forum', t('Social'), 'dashboard/social', 'Garden.Settings.Manage', array('class' => 'nav-social-settings'));

        $Menu->AddItem('Reputation', t('Reputation'), false, array('class' => 'Reputation'));

        $Menu->AddItem('Add-ons', t('Addons'), false, array('class' => 'Addons'));
        $Menu->addLink('Add-ons', t('Plugins'), 'dashboard/settings/plugins', 'Garden.Settings.Manage', array('class' => 'nav-addons nav-plugins'));
        $Menu->addLink('Add-ons', t('Applications'), 'dashboard/settings/applications', 'Garden.Settings.Manage', array('class' => 'nav-addons nav-applications'));
        $Menu->addLink('Add-ons', t('Locales'), 'dashboard/settings/locales', 'Garden.Settings.Manage', array('class' => 'nav-addons nav-locales'));

        $Menu->AddItem('Site Settings', t('Settings'), false, array('class' => 'SiteSettings'));
        $Menu->addLink('Site Settings', t('Outgoing Email'), 'dashboard/settings/email', 'Garden.Settings.Manage', array('class' => 'nav-email nav-email-out'));
        $Menu->addLink('Site Settings', t('Routes'), 'dashboard/routes', 'Garden.Settings.Manage', array('class' => 'nav-routes'));
        $Menu->addLink('Site Settings', t('Statistics'), 'dashboard/statistics', 'Garden.Settings.Manage', array('class' => 'nav-statistics-settings'));

        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            $Menu->AddItem('Import', t('Import'), 'Garden.Settings.Manage', array('class' => 'Import'));
            $Menu->addLink('Import', false, 'dashboard/import', 'Garden.Settings.Manage', array('class' => 'nav-import'));
        }
    }

    /**
     * Set P3P header because IE won't allow cookies thru the iFrame without it.
     *
     * This must be done in the Dispatcher because of PrivateCommunity.
     * That precludes using Controller->SetHeader.
     * This is done so comment & forum embedding can work in old IE.
     *
     * @param Gdn_Dispatcher $Sender
     */
    public function gdn_Dispatcher_AppStartup_Handler($Sender) {
        safeHeader('P3P: CP="CAO PSA OUR"', true);

        if ($SSO = Gdn::request()->get('sso')) {
            saveToConfig('Garden.Registration.SendConnectEmail', false, false);

            $IsApi = preg_match('`\.json$`i', Gdn::request()->Path());

            $UserID = false;
            try {
                $CurrentUserID = Gdn::session()->UserID;
                $UserID = Gdn::userModel()->SSO($SSO);
            } catch (Exception $Ex) {
                trace($Ex, TRACE_ERROR);
            }

            if ($UserID) {
                Gdn::session()->start($UserID, !$IsApi, !$IsApi);
                if ($IsApi) {
                    Gdn::session()->validateTransientKey(true);
                }

                if ($UserID != $CurrentUserID) {
                    Gdn::userModel()->fireEvent('AfterSignIn');
                }
            } else {
                // There was some sort of error. Let's print that out.
                trace(Gdn::userModel()->Validation->resultsText(), TRACE_WARNING);
            }
        }
    }

    /**
     * Method for plugins that want a friendly /sso method to hook into.
     *
     * @param RootController $Sender
     * @param string $Target The url to redirect to after sso.
     */
    public function rootController_SSO_Create($Sender, $Target = '') {
        if (!$Target) {
            $Target = $Sender->Request->get('redirect');
            if (!$Target) {
                $Target = '/';
            }
        }

        // TODO: Make sure the target is a safe redirect.

        // Get the default authentication provider.
        $DefaultProvider = Gdn_AuthenticationProviderModel::GetDefault();
        $Sender->EventArguments['Target'] = $Target;
        $Sender->EventArguments['DefaultProvider'] = $DefaultProvider;
        $Handled = false;
        $Sender->EventArguments['Handled'] =& $Handled;

        $Sender->fireEvent('SSO');

        // If an event handler didn't handle the signin then just redirect to the target.
        if (!$Handled) {
            redirect($Target, 302);
        }
    }

    /**
     *
     *
     * @param SiteNavModule $sender
     */
    public function siteNavModule_all_handler($sender) {
        // Add a link to the community home.
        $sender->addLink('main.home', array('text' => t('Community Home'), 'url' => '/', 'icon' => icon('home'), 'sort' => -100));

        $sender->addGroup('etc', array('sort' => 100));
        if (Gdn::session()->isValid()) {
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
     *
     *
     * @param SiteNavModule $sender
     */
    public function siteNavModule_default_handler($sender) {
        if (Gdn::session()->isValid()) {
            $sender->addLink('main.profile', array('text' => t('Profile'), 'url' => '/profile', 'icon' => icon('user'), 'sort' => 10));
        }
        if (Gdn::session()->checkPermission('Garden.Activity.View')) {
            $sender->addLink('main.activity', array('text' => t('Activity'), 'url' => '/activity', 'icon' => icon('time'), 'sort' => 10));
        }

        // Add the moderation items.
        $sender->addGroup('moderation', array('text' => t('Moderation'), 'sort' => 90));
        if (Gdn::session()->checkPermission('Garden.Users.Approve')) {
            $RoleModel = new RoleModel();
            $applicant_count = (int)$RoleModel->GetApplicantCount();
            if ($applicant_count > 0 || true) {
                $sender->addLink('moderation.applicants', array('text' => t('Applicants'), 'url' => '/user/applicants', 'icon' => icon('user'), 'badge' => countString($applicant_count)));
            }
        }

        if (Gdn::session()->checkPermission('Garden.Modertion.Manage')) {
            $sender->addLink('moderation.spam', array('text' => 'Spam Queue', 'url' => '/log/spam', 'icon' => icon('spam')));
//         $sender->addLink('moderation.queue', array('text' => 'Moderaton Queue', 'url' => '/log/moderation', 'icon' => icon('report')));
        }

        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            $sender->addLink('etc.dashboard', array('text' => t('Dashboard'), 'url' => '/settings', 'icon' => icon('dashboard')));
        }
    }

    /**
     *
     *
     * @param SiteNavModule $sender
     */
    public function siteNavModule_editprofile_handler($sender) {
        $user = Gdn::controller()->data('Profile');
        $user_id = val('UserID', $user);
        $is_me = $user_id == Gdn::session()->UserID;

        if (!Gdn::session()->isValid()) {
            return;
        }

        // Users can edit their own profiles and moderators can edit any profile.
        if (hasEditProfile($user_id)) {
            $sender->addLink('main.editprofile', array('text' => t('Profile'), 'url' => userUrl($user, '', 'edit'), 'icon' => icon('edit')));
        }

        if (checkPermission('Garden.Users.Edit')) {
            $sender->addLink('main.editaccount', array('text' => t('Edit Account'), 'url' => "/user/edit/$user_id", 'icon' => icon('cog'), 'class' => 'Popup'));
        }

        $sender->addLink('main.profile', array('text' => t('Back to Profile'), 'url' => userUrl('user'), 'icon' => icon('arrow-left'), 'sort' => 100));
    }

    /**
     *
     *
     * @param SiteNavModule $sender
     */
    public function siteNavModule_profile_handler($sender) {
        $user = Gdn::controller()->data('Profile');
        $user_id = val('UserID', $user);

        // Show the activity.
        if (c('Garden.Profile.ShowActivities', true)) {
            $sender->addLink('main.activity', array('text' => t('Activity'), 'url' => userUrl($user, '', 'activity'), 'icon' => icon('time')));
        }

        // Display the notifications for the current user.
        if (Gdn::controller()->data('Profile.UserID') == Gdn::session()->UserID) {
            $sender->addLink('main.notifications', array('text' => t('Notifications'), 'url' => userUrl($user, '', 'notifications'), 'icon' => icon('globe'), 'badge' => Gdn::controller()->data('Profile.CountNotifications')));
        }

        // Show the invitations if we're using the invite registration method.
        if (strcasecmp(c('Garden.Registration.Method'), 'invitation') === 0) {
            $sender->addLink('main.invitations', array('text' => t('Invitations'), 'url' => userUrl($user, '', 'invitations'), 'icon' => icon('ticket')));
        }

        // Users can edit their own profiles and moderators can edit any profile.
        if (hasEditProfile($user_id)) {
            $sender->addLink('main.editprofile', array('text' => t('Edit Profile'), 'url' => userUrl($user, '', 'edit'), 'icon' => icon('edit')));
        }

        // Add a stub group for moderation.
        $sender->addGroup('moderation', array('text' => t('Moderation'), 'sort' => 90));
    }
}
