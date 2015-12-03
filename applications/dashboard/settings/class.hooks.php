<?php
/**
 * DashboardHooks class.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
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
    public function base_render_before($Sender) {
        $Session = Gdn::session();

        // Enable theme previewing
        if ($Session->isValid()) {
            $PreviewThemeName = htmlspecialchars($Session->getPreference('PreviewThemeName', ''));
            $PreviewThemeFolder = htmlspecialchars($Session->getPreference('PreviewThemeFolder', ''));
            if ($PreviewThemeName != '') {
                $Sender->Theme = $PreviewThemeName;
                $Sender->informMessage(
                    sprintf(t('You are previewing the %s theme.'), wrap($PreviewThemeName, 'em'))
                    .'<div class="PreviewThemeButtons">'
                    .anchor(t('Apply'), 'settings/themes/'.$PreviewThemeName.'/'.$Session->transientKey(), 'PreviewThemeButton')
                    .' '.anchor(t('Cancel'), 'settings/cancelpreview/', 'PreviewThemeButton')
                    .'</div>',
                    'DoNotDismiss'
                );
            }
        }

        if ($Session->isValid()) {
            $Confirmed = val('Confirmed', Gdn::session()->User, true);
            if (UserModel::requireConfirmEmail() && !$Confirmed) {
                $Message = formatString(t('You need to confirm your email address.', 'You need to confirm your email address. Click <a href="{/entry/emailconfirmrequest,url}">here</a> to resend the confirmation email.'));
                $Sender->informMessage($Message, '');
            }
        }

        // Add Message Modules (if necessary)
        $MessageCache = Gdn::config('Garden.Messages.Cache', array());
        $Location = $Sender->Application.'/'.substr($Sender->ControllerName, 0, -10).'/'.$Sender->RequestMethod;
        $Exceptions = array('[Base]');

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
            $MessageData = $MessageModel->getMessagesForLocation($Location, $Exceptions, $Sender->data('Category.CategoryID'));
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
            $Gdn_Statistics = Gdn::factory('Statistics');
            $Gdn_Statistics->check($Sender);
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
            if ($remoteUrlFormat = c('Garden.Embed.RemoteUrlFormat')) {
                $Sender->addDefinition('RemoteUrlFormat', $remoteUrlFormat);
            }

            // Force embedding?
            if (!IsSearchEngine() && strtolower($Sender->ControllerName) != 'entry') {
                if (IsMobile()) {
                    $forceEmbedForum = c('Garden.Embed.ForceMobile') ? '1' : '0';
                } else {
                    $forceEmbedForum = c('Garden.Embed.ForceForum') ? '1' : '0';
                }

                $Sender->addDefinition('ForceEmbedForum', $forceEmbedForum);
                $Sender->addDefinition('ForceEmbedDashboard', c('Garden.Embed.ForceDashboard') ? '1' : '0');
            }

            $Sender->addDefinition('Path', Gdn::request()->path());
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
            $Sender->addAsset('Foot', wrap(Anchor(t('Back to Mobile Site'), '/profile/nomobile/1'), 'div'), 'MobileLink');
        }

        // Allow global translation of TagHint
        $Sender->addDefinition("TagHint", t("TagHint", "Start to type..."));
    }

    /**
     * @param $Sender
     */
    public function base_getAppSettingsMenuItems_handler($Sender) {
	$session = Gdn::session();
	$themeOptionsName = c('Garden.ThemeOptions.Name');
	$mobileThemeOptionsName = c('Garden.MobileThemeOptions.Name');

        // SideMenuModule menu
        $menu = &$Sender->EventArguments['Nav'];
        $menu->addGroup(t('Dashboard'), 'dashboard')
            ->addLinkIf('Garden.Settings.View', t('Dashboard'), '/dashboard/settings', 'dashboard.dashboard')
            ->addLinkIf('Garden.Settings.Manage', t('Getting Started'), '/dashboard/settings/gettingstarted', 'dashboard.getting-started')
            ->addLinkIf('Garden.Settings.View', t('Help &amp Tutorials'), '/dashboard/settings/tutorials', 'dashboard.tutorials')
            ->addGroup(t('Appearance'), 'appearance')
            ->addLinkIf('Garden.Settings.Manage', t('Banner'), '/dashboard/settings/banner', 'appearance.banner')
            ->addLinkIf('Garden.Settings.Manage', t('Homepage'), '/dashboard/settings/homepage', 'appearance.homepage')
            ->addLinkIf('Garden.Settings.Manage', t('Themes'), '/dashboard/settings/themes', 'appearance.themes')
            ->addLinkIf($themeOptionsName && $session->checkPermission('Garden.Settings.Manage'), t('Theme Options'), '/dashboard/settings/themeoptions', 'appearance.theme-options')
            ->addLinkIf('Garden.Settings.Manage', t('Mobile Theme'), '/dashboard/settings/mobilethemes', 'appearance.mobile-themes')
            ->addLinkIf($mobileThemeOptionsName && $session->checkPermission('Garden.Settings.Manage'), t('Mobile Theme Options'), '/dashboard/settings/mobilethemeoptions', 'appearance.mobile-theme-options')
            ->addLinkIf('Garden.Community.Manage', t('Messages'), '/dashboard/message', 'appearance.messages')
            ->addLinkIf('Garden.Community.Manage', t('Avatars'), '/dashboard/settings/avatars', 'appearance.avatars')
            ->addGroup(t('Users'), 'users')
            ->addLinkIf(array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'), t('Users'), '/dashboard/user', 'users.users')
            ->addLinkIf($session->checkPermission(array('Garden.Settings.Manage', 'Garden.Roles.Manage'), false), t('Roles & Permissions'), '/dashboard/role', 'users.roles')
            ->addLinkIf('Garden.Settings.Manage', t('Registration'), '/dashboard/settings/registration', 'users.registration')
            ->addLinkIf('Garden.Settings.Manage', t('Authentication'), '/dashboard/authentication', 'users.authentication')
            ->addLinkIf($session->checkPermission('Garden.Users.Approve') && (c('Garden.Registration.Method') == 'Approval'), t('Applicants'), '/dashboard/user/applicants', 'users.applicants', '', array(), false, array('popinRel' => '/dashboard/user/applicantcount'))
            ->addGroup(t('Moderation'), 'moderation')
            ->addLinkIf($session->checkPermission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage'), false), t('Spam Queue'), '/dashboard/log/spam', 'moderation.spam-queue')
            ->addLinkIf($session->checkPermission(array('Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'), false), t('Moderation Queue'), '/dashboard/log/moderation', 'moderation.moderation-queue', '', array(), false, array('popinRel' => '/dashboard/log/count/moderate'))
            ->addLinkIf('Garden.Settings.Manage', t('Authentication'), '/dashboard/log/edits', 'moderation.change-log')
            ->addLinkIf('Garden.Community.Manage', t('Banning'), '/dashboard/settings/bans', 'moderation.bans')
            ->addGroup(t('Forum Settings'), 'forum')
	    ->addLinkIf('Garden.Settings.Manage', t('Social'), '/social/manage', 'forum.social')
            ->addGroup(t('Reputation'), 'reputation')
            ->addGroup(t('Addons'), 'add-ons')
            ->addLinkIf('Garden.Settings.Manage', t('Plugins'), '/dashboard/settings/plugins', 'add-ons.plugins')
            ->addLinkIf('Garden.Settings.Manage', t('Applications'), '/dashboard/settings/applications', 'add-ons.applications')
            ->addLinkIf('Garden.Settings.Manage', t('Locales'), '/dashboard/settings/locales', 'add-ons.locales')
            ->addGroup(t('Site Settings'), 'site-settings')
            ->addLinkIf('Garden.Settings.Manage', t('Outgoing Email'), '/dashboard/settings/email', 'site-settings.email')
            ->addLinkIf('Garden.Settings.Manage', t('Routes'), '/dashboard/routes', 'site-settings.routes')
            ->addLinkIf('Garden.Settings.Manage', t('Statistics'), '/dashboard/statistics', 'site-settings.statistics')
            ->addGroupIf('Garden.Settings.Manage', t('Import'), 'import')
            ->addLinkIf('Garden.Settings.Manage', t('Import'), '/dashboard/import', 'import.import');
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
    public function gdn_dispatcher_appStartup_handler($Sender) {
        safeHeader('P3P: CP="CAO PSA OUR"', true);

        if ($SSO = Gdn::request()->get('sso')) {
            saveToConfig('Garden.Registration.SendConnectEmail', false, false);

            $IsApi = preg_match('`\.json$`i', Gdn::request()->path());

            $UserID = false;
            try {
                $CurrentUserID = Gdn::session()->UserID;
                $UserID = Gdn::userModel()->sso($SSO);
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
                foreach (Gdn::userModel()->Validation->resultsArray() as $msg) {
                    trace($msg, TRACE_ERROR);
                }
                Gdn::userModel()->Validation->reset();
            }
        }
    }

    /**
     * Method for plugins that want a friendly /sso method to hook into.
     *
     * @param RootController $Sender
     * @param string $Target The url to redirect to after sso.
     */
    public function rootController_sso_create($Sender, $Target = '') {
        if (!$Target) {
            $Target = $Sender->Request->get('redirect');
            if (!$Target) {
                $Target = '/';
            }
        }

        // TODO: Make sure the target is a safe redirect.

        // Get the default authentication provider.
        $DefaultProvider = Gdn_AuthenticationProviderModel::getDefault();
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
	$sender->addLink(t('Community Home'), '/', 'main.home', '', -100, array('icon' => 'home'), false);
	$sender->addGroup('', 'etc', '', 100);
	$sender->addLinkIf(Gdn::session()->isValid() && IsMobile(), t('Full Site'), '/profile/nomobile', 'etc.nomobile', '', 100, array('icon' => 'resize-full'));
	$sender->addLinkIf(Gdn::session()->isValid(), t('Sign Out'), SignOutUrl(), 'etc.signout', '', 100, array('icon' => 'signout'));
	$sender->addLinkIf(!Gdn::session()->isValid(), t('Sign In'), SigninUrl(), 'etc.signin', '', 100, array('icon' => 'signin'));
    }

    /**
     *
     *
     * @param SiteNavModule $sender
     */
    public function siteNavModule_default_handler($sender) {

	$sender->addLinkIf(Gdn::session()->isValid(), t('Profile'), '/profile', 'main.profile', 'profile', 10, array('icon' => 'user'));
	$sender->addLinkIf('Garden.Activity.View', t('Activity'), '/activity', 'main.activity', 'activity', 10, array('icon' => 'time'));

        // Add the moderation items.
	$sender->addGroup(t('Moderation'), 'moderation', 'moderation', 90);
        if (Gdn::session()->checkPermission('Garden.Users.Approve')) {
            $RoleModel = new RoleModel();
            $applicant_count = (int)$RoleModel->getApplicantCount();
            if ($applicant_count > 0 || true) {
		$sender->addLink(t('Applicants'), '/user/applicants', 'moderation.applicants', 'applicants', array(), array('icon' => 'user', 'badge' => $applicant_count));
            }
        }
	$sender->addLinkIf('Garden.Moderation.Manage', t('Spam Queue'), '/log/spam', 'moderation.spam', 'spam', array(), array('icon' => 'spam'));
	$sender->addLinkIf('Garden.Settings.Manage', t('Dashboard'), '/settings', 'etc.dashboard', 'dashboard', array(), array('icon' => 'dashboard'));
    }

    /**
     *
     *
     * @param SiteNavModule $sender
     */
    public function siteNavModule_editProfile_handler($sender) {
        $user = Gdn::controller()->data('Profile');
        $user_id = val('UserID', $user);

        if (!Gdn::session()->isValid()) {
            return;
        }

	// Users can edit their own profiles and moderators can edit any profile.
	$sender->addLinkIf(hasEditProfile($user_id), t('Profile'), userUrl($user, '', 'edit'), 'main.editprofile', '', array(), array('icon' => 'edit'))
	    ->addLinkIf('Garden.Users.Edit', t('Edit Account'), '/user/edit/'.$user_id, 'main.editaccount', 'Popup', array(), array('icon' => 'cog'))
	    ->addLink(t('Back to Profile'), userUrl($user), 'main.profile', '', 100, array('icon' => 'arrow-left'));
    }

    /**
     *
     *
     * @param SiteNavModule $sender
     */
    public function siteNavModule_profile_handler($sender) {
        $user = Gdn::controller()->data('Profile');
        $user_id = val('UserID', $user);

        $sender->addLinkIf(c('Garden.Profile.ShowActivities', true), t('Activity'), userUrl($user, '', 'activity'), 'main.activity', '', array(), array('icon' => 'time'));
	$sender->addLinkIf(Gdn::controller()->data('Profile.UserID') == Gdn::session()->UserID, t('Notifications'), userUrl($user, '', 'notifications'), 'main.notifications', '', array(), array('icon' => 'globe', 'badge' => Gdn::controller()->data('Profile.CountNotifications')));

        // Show the invitations if we're using the invite registration method.
	$sender->addLinkIf(strcasecmp(c('Garden.Registration.Method'), 'invitation') === 0, t('Invitations'), userUrl($user, '', 'invitations'), 'main.invitations', '', array(), array('icon' => 'ticket'));

        // Users can edit their own profiles and moderators can edit any profile.
	$sender->addLinkIf(hasEditProfile($user_id), t('Edit Profile'), userUrl($user, '', 'edit'), 'main.editprofile', '', array(), array('icon' => 'edit'));

        // Add a stub group for moderation.
	$sender->addGroup(t('Moderation'), 'moderation', '', 90);
    }

    /**
     * After executing /settings/utility/update check if any role permissions have been changed, if not reset all the permissions on the roles.
     * @param $sender
     */
    public function updateModel_afterStructure_handler($sender) {
        // Only setup default permissions if no role permissions are set.
        $hasPermissions = Gdn::sql()->getWhere('Permission', array('RoleID >' => 0))->firstRow(DATASET_TYPE_ARRAY);
        if (!$hasPermissions) {
            PermissionModel::resetAllRoles();
        }
    }

    /**
     * Add user's viewable roles to gdn.meta if user is logged in.
     * @param $sender
     * @param $args
     */
    public function gdn_dispatcher_afterControllerCreate_handler($sender, $args) {
        // Function addDefinition returns the value of the definition if you pass only one argument.
        if (!gdn::controller()->addDefinition('Roles')) {
            if (Gdn::session()->isValid()) {
                $roleModel = new RoleModel();
                gdn::controller()->addDefinition("Roles", $roleModel->getPublicUserRoles(gdn::session()->UserID, "Name"));
            }
        }
    }
}
