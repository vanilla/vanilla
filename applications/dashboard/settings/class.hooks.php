<?php
/**
 * DashboardHooks class.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

use Garden\Container\Container;
use Garden\Container\Reference;

/**
 * Event handlers for the Dashboard application.
 */
class DashboardHooks extends Gdn_Plugin {

    /**
     * Install the formatter to the container.
     *
     * @param Container $dic The container to initialize.
     */
    public function container_init_handler(Container $dic) {
        $dic->rule('HeadModule')
            ->setShared(true)
            ->addAlias('Head')

            ->rule('MenuModule')
            ->setShared(true)
            ->addAlias('Menu')

            ->rule('Gdn_Dispatcher')
            ->addCall('passProperty', ['Menu', new Reference('MenuModule')])
            ;
    }

    /**
     * Fire before every page render.
     *
     * @param Gdn_Controller $Sender
     */
    public function base_render_before($Sender) {
        $Session = Gdn::session();


        if ($Sender->MasterView == 'admin') {
            if (val('Form', $Sender)) {
                $Sender->Form->setStyles('bootstrap');
            }

            $Sender->CssClass = htmlspecialchars($Sender->CssClass);
            $Sections = Gdn_Theme::section(null, 'get');
            if (is_array($Sections)) {
                foreach ($Sections as $Section) {
                    $Sender->CssClass .= ' Section-'.$Section;
                }
            }

            // Get our plugin nav items.
            $navAdapter = new NestedCollectionAdapter(DashboardNavModule::getDashboardNav());
            $Sender->EventArguments['SideMenu'] = $navAdapter;
            $Sender->fireEvent('GetAppSettingsMenuItems');

            $Sender->removeJsFile('jquery.popup.js');
            $Sender->addJsFile('vendors/jquery.checkall.min.js', 'dashboard');
            $Sender->addJsFile('buttongroup.js', 'dashboard');
            $Sender->addJsFile('dashboard.js', 'dashboard');
            $Sender->addJsFile('jquery.expander.js');
            $Sender->addJsFile('settings.js', 'dashboard');
            $Sender->addJsFile('vendors/tether.min.js', 'dashboard');
            $Sender->addJsFile('vendors/bootstrap/util.js', 'dashboard');
            $Sender->addJsFile('vendors/drop.min.js', 'dashboard');
            $Sender->addJsFile('vendors/moment.min.js', 'dashboard');
            $Sender->addJsFile('vendors/daterangepicker.js', 'dashboard');
            $Sender->addJsFile('vendors/bootstrap/tooltip.js', 'dashboard');
            $Sender->addJsFile('vendors/clipboard.min.js', 'dashboard');
            $Sender->addJsFile('vendors/bootstrap/dropdown.js', 'dashboard');
            $Sender->addJsFile('vendors/bootstrap/collapse.js', 'dashboard');
            $Sender->addJsFile('vendors/bootstrap/modal.js', 'dashboard');
            $Sender->addJsFile('vendors/icheck.min.js', 'dashboard');
            $Sender->addJsFile('jquery.tablejenga.js', 'dashboard');
            $Sender->addJsFile('jquery.fluidfixed.js', 'dashboard');
            $Sender->addJsFile('vendors/prettify/prettify.js', 'dashboard');
            $Sender->addJsFile('vendors/ace/ace.js', 'dashboard');
            $Sender->addJsFile('vendors/ace/ext-searchbox.js', 'dashboard');
            $Sender->addCssFile('vendors/tomorrow.css', 'dashboard');
        }

        // Check the statistics.
        if ($Sender->deliveryType() == DELIVERY_TYPE_ALL) {
            Gdn::statistics()->check();
        }

        // Inform user of theme previewing
        if ($Session->isValid()) {
            $PreviewThemeFolder = htmlspecialchars($Session->getPreference('PreviewThemeFolder', ''));
            $PreviewMobileThemeFolder = htmlspecialchars($Session->getPreference('PreviewMobileThemeFolder', ''));
            $PreviewThemeName = htmlspecialchars($Session->getPreference(
                'PreviewThemeName',
                $PreviewThemeFolder
            ));
            $PreviewMobileThemeName = htmlspecialchars($Session->getPreference(
                'PreviewMobileThemeName',
                $PreviewMobileThemeFolder
            ));

            if ($PreviewThemeFolder != '') {
                $Sender->informMessage(
                    sprintf(t('You are previewing the %s desktop theme.'), wrap($PreviewThemeName, 'em'))
                    .'<div class="PreviewThemeButtons">'
                    .anchor(t('Apply'), 'settings/themes/'.$PreviewThemeFolder.'/'.$Session->transientKey(), 'PreviewThemeButton')
                    .' '.anchor(t('Cancel'), 'settings/cancelpreview/'.$PreviewThemeFolder.'/'.$Session->transientKey(), 'PreviewThemeButton')
                    .'</div>',
                    'DoNotDismiss'
                );
            }

            if ($PreviewMobileThemeFolder != '') {
                $Sender->informMessage(
                    sprintf(t('You are previewing the %s mobile theme.'), wrap($PreviewMobileThemeName, 'em'))
                    .'<div class="PreviewThemeButtons">'
                    .anchor(t('Apply'), 'settings/mobilethemes/'.$PreviewMobileThemeFolder.'/'.$Session->transientKey(), 'PreviewThemeButton')
                    .' '.anchor(t('Cancel'), 'settings/cancelpreview/'.$PreviewMobileThemeFolder.'/'.$Session->transientKey(), 'PreviewThemeButton')
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

            $get = Gdn::request()->get();
            unset($get['p']); // kludge for old index.php?p=/path
            $Sender->addDefinition('Query', http_build_query($get));
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
            $Sender->addAsset('Foot', wrap(Anchor(t('Back to Mobile Site'), '/profile/nomobile/1', 'js-hijack'), 'div'), 'MobileLink');
        }

        // Allow global translation of TagHint
        if (c('Tagging.Discussions.Enabled')) {
            $Sender->addDefinition('TaggingAdd', Gdn::session()->checkPermission('Vanilla.Tagging.Add'));
            $Sender->addDefinition('TaggingSearchUrl', Gdn::request()->Url('tags/search'));
            $Sender->addDefinition('MaxTagsAllowed', c('Vanilla.Tagging.Max', 5));
            $Sender->addDefinition('TagHint', t('TagHint', 'Start to type...'));
        }



        // Add symbols.
        if ($Sender->deliveryMethod() === DELIVERY_METHOD_XHTML) {
            $Sender->addAsset('Symbols', $Sender->fetchView('symbols', '', 'Dashboard'));
        }
    }

    /**
     * Checks if the user is previewing a theme and, if so, updates the default master view.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeFetchMaster_handler($sender) {
        $session = Gdn::session();
        if (!$session->isValid()) {
            return;
        }
        if (isMobile()) {
            $theme = htmlspecialchars($session->getPreference('PreviewMobileThemeFolder', ''));
        } else {
            $theme = htmlspecialchars($session->getPreference('PreviewThemeFolder', ''));
        }
        $isDefaultMaster = $sender->MasterView == 'default' || $sender->MasterView == '';
        if ($theme != '' && $isDefaultMaster) {
            $htmlFile = paths(PATH_THEMES, $theme, 'views', 'default.master.tpl');
            if (file_exists($htmlFile)) {
                $sender->EventArguments['MasterViewPath'] = $htmlFile;
            } else {
                // for default theme
                $sender->EventArguments['MasterViewPath'] = $sender->fetchViewLocation('default.master', '', 'dashboard');
            }
        }
    }

    /**
     * Setup dashboard navigation.
     *
     * @param $sender
     */
    public function dashboardNavModule_init_handler($sender) {
        /** @var DashboardNavModule $nav */
        $nav = $sender;

        $session = Gdn::session();
        $hasThemeOptions = Gdn::themeManager()->hasThemeOptions(Gdn::themeManager()->getEnabledDesktopThemeKey());
        $hasMobileThemeOptions = Gdn::themeManager()->hasThemeOptions(Gdn::themeManager()->getEnabledMobileThemeKey());

        $sort = -1; // Ensure these nav items come before any plugin nav items.

        $nav->addGroupToSection('Moderation', t('Site'), 'site')
            ->addLinkToSectionIf('Garden.Community.Manage', 'Moderation', t('Messages'), '/dashboard/message', 'site.messages', '', $sort)
            ->addLinkToSectionIf($session->checkPermission(['Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'], false), 'Moderation', t('Users'), '/dashboard/user', 'site.users', '', $sort)
            ->addLinkToSectionIf($session->checkPermission('Garden.Users.Approve') && (c('Garden.Registration.Method') == 'Approval'), 'Moderation', t('Applicants'), '/dashboard/user/applicants', 'site.applicants', '', $sort, ['popinRel' => '/dashboard/user/applicantcount'], false)
            ->addLinkToSectionIf('Garden.Settings.Manage', 'Moderation', t('Ban Rules'), '/dashboard/settings/bans', 'site.bans', '', $sort)

            ->addGroupToSection('Moderation', t('Content'), 'moderation')
            ->addLinkToSectionIf($session->checkPermission(['Garden.Moderation.Manage', 'Moderation.Spam.Manage'], false), 'Moderation', t('Spam Queue'), '/dashboard/log/spam', 'moderation.spam-queue', '', $sort)
            ->addLinkToSectionIf($session->checkPermission(['Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'], false), 'Moderation', t('Moderation Queue'), '/dashboard/log/moderation', 'moderation.moderation-queue', '', $sort, ['popinRel' => '/dashboard/log/count/moderate'], false)
            ->addLinkToSectionIf($session->checkPermission(['Garden.Settings.Manage', 'Garden.Moderation.Manage'], false), 'Moderation', t('Change Log'), '/dashboard/log/edits', 'moderation.change-log', '', $sort)

            ->addGroup(t('Appearance'), 'appearance', '', -1)
            ->addLinkIf($session->checkPermission(['Garden.Settings.Manage', 'Garden.Community.Manage'], false), t('Branding'), '/dashboard/settings/branding', 'appearance.banner', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Layout'), '/dashboard/settings/layout', 'appearance.layout', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Themes'), '/dashboard/settings/themes', 'appearance.themes', '', $sort)
            ->addLinkIf($hasThemeOptions && $session->checkPermission('Garden.Settings.Manage'), t('Theme Options'), '/dashboard/settings/themeoptions', 'appearance.theme-options', '', $sort)
            ->addLinkIf($hasMobileThemeOptions && $session->checkPermission('Garden.Settings.Manage'), t('Mobile Theme Options'), '/dashboard/settings/mobilethemeoptions', 'appearance.mobile-theme-options', '', $sort)
            ->addLinkIf('Garden.Community.Manage', t('Avatars'), '/dashboard/settings/avatars', 'appearance.avatars', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Email'), '/dashboard/settings/emailstyles', 'appearance.email', '', $sort)

            ->addGroup(t('Membership'), 'users', '', ['after' => 'appearance'])
            ->addLinkIf($session->checkPermission(['Garden.Settings.Manage', 'Garden.Roles.Manage'], false), t('Roles & Permissions'), '/dashboard/role', 'users.roles', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Registration'), '/dashboard/settings/registration', 'users.registration', '', $sort)

            ->addGroup(t('Discussions'), 'forum', '', ['after' => 'users'])
            ->addLinkIf('Garden.Settings.Manage', t('Tagging'), 'settings/tagging', 'forum.tagging', $sort)

            ->addGroup(t('Reputation'), 'reputation', '', ['after' => 'forum'])

            ->addGroup(t('Addons'), 'add-ons', '', ['after' => 'reputation'])
            ->addLinkIf('Garden.Settings.Manage', t('Social Connect'), '/social/manage', 'add-ons.social', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Plugins'), '/dashboard/settings/plugins', 'add-ons.plugins', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Applications'), '/dashboard/settings/applications', 'add-ons.applications', '', $sort)

            ->addGroup(t('Technical'), 'site-settings', '', ['after' => 'reputation'])
            ->addLinkIf('Garden.Settings.Manage', t('Locales'), '/dashboard/settings/locales', 'site-settings.locales', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Outgoing Email'), '/dashboard/settings/email', 'site-settings.email', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Security'), '/dashboard/settings/security', 'site-settings.security', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Routes'), '/dashboard/routes', 'site-settings.routes', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Statistics'), '/dashboard/statistics', 'site-settings.statistics', '', $sort)

            ->addGroupIf('Garden.Settings.Manage', t('Forum Data'), 'forum-data', '', ['after' => 'site-settings'])
            ->addLinkIf('Garden.Settings.Manage', t('Import'), '/dashboard/import', 'forum-data.import', '', $sort);
    }

    /**
     * Aggressively prompt users to upgrade PHP version.
     *
     * @param $sender
     */
    public function settingsController_render_before($sender) {
        // Set this in your config to dismiss our upgrade warnings. Not recommended.
        if (c('Vanilla.WarnedMeToUpgrade') === 'PHP 5.6') {
            return;
        }

        if (version_compare(phpversion(), '5.6') < 0) {
            $UpgradeMessage = ['Content' => 'Upgrade to <b>PHP 5.6</b> or higher immediately. Version '.phpversion().' is no longer supported.', 'AssetTarget' => 'Content', 'CssClass' => 'WarningMessage'];
            $MessageModule = new MessageModule($sender, $UpgradeMessage);
            $sender->addModule($MessageModule);
        }

        $mysqlVersion = gdn::sql()->version();
        if (version_compare($mysqlVersion, '5.6') < 0) {
            $UpgradeMessage = ['Content' => 'We recommend using <b>MySQL 5.6</b> or higher. Version '.htmlspecialchars($mysqlVersion).' will not support all upcoming Vanilla features.', 'AssetTarget' => 'Content', 'CssClass' => 'InfoMessage'];
            $MessageModule = new MessageModule($sender, $UpgradeMessage);
            $sender->addModule($MessageModule);
        }
    }

    /**
     * List all tags and allow searching
     *
     * @param SettingsController $Sender
     */
    public function settingsController_tagging_create($Sender, $Search = null, $Type = null, $Page = null) {
        $Sender->title('Tagging');
        $Sender->setHighlightRoute('settings/tagging');
        $SQL = Gdn::sql();

        /** @var Gdn_Form $form */
        $form = $Sender->Form;

        if ($form->authenticatedPostBack()) {
            $formValue = (bool)$form->getFormValue('Tagging.Discussions.Enabled');
            saveToConfig('Tagging.Discussions.Enabled', $formValue);
        }

        // Get all tag types
        $TagModel = TagModel::instance();
        $TagTypes = $TagModel->getTagTypes();


        list($Offset, $Limit) = offsetLimit($Page, 100);
        $Sender->setData('_Limit', $Limit);

        if ($Search) {
            $SQL->like('Name', $Search, 'right');
        }

        $queryType = $Type;

        if (strtolower($Type) == 'all' || $Search || $Type === null) {
            $queryType = false;
            $Type = '';
        }

        // This type doesn't actually exist, but it will represent the blank types in the column.
        if (strtolower($Type) == 'tags') {
            $queryType = '';
        }

        if (!$Search && ($queryType !== false)) {
            $SQL->where('Type', $queryType);
        }

        $TagTypes = array_change_key_case($TagTypes, CASE_LOWER);

        // Store type for view
        $TagType = !empty($Type) ? $Type : 'All';
        $Sender->setData('_TagType', $TagType);

        // Store tag types
        $Sender->setData('_TagTypes', $TagTypes);

        // Determine if new tags can be added for the current type.
        $CanAddTags = (!empty($TagTypes[$Type]['addtag']) && $TagTypes[$Type]['addtag']) ? 1 : 0;
        $CanAddTags &= CheckPermission('Vanilla.Tagging.Add');

        $Sender->setData('_CanAddTags', $CanAddTags);

        $Data = $SQL
            ->select('t.*')
            ->from('Tag t')
            ->orderBy('t.CountDiscussions', 'desc')
            ->limit($Limit, $Offset)
            ->get()->resultArray();

        $Sender->setData('Tags', $Data);

        if ($Search) {
            $SQL->like('Name', $Search, 'right');
        }

        // Make sure search uses its own search type, so results appear in their own tab.
        $Sender->Form->Action = url('/settings/tagging/?type='.$TagType);

        // Search results pagination will mess up a bit, so don't provide a type in the count.
        $RecordCountWhere = array('Type' => $queryType);
        if ($queryType === false) {
            $RecordCountWhere = [];
        }
        if ($Search) {
            $RecordCountWhere = array();
        }

        $Sender->setData('RecordCount', $SQL->getCount('Tag', $RecordCountWhere));
        $Sender->render('tagging');
    }

    /**
     * Add the tags endpoint to the settingsController
     *
     * @param SettingsController $Sender
     * @param string $action
     *
     */
    public function settingsController_tags_create($Sender, $action) {
        $Sender->permission('Garden.Settings.Manage');

        switch($action) {
            case 'delete':
                $TagID = val(1, $Sender->RequestArgs);
                $TagModel = new TagModel();
                $Tag = $TagModel->getID($TagID, DATASET_TYPE_ARRAY);

                if ($Sender->Form->authenticatedPostBack()) {
                    // Delete tag & tag relations.
                    $SQL = Gdn::sql();
                    $SQL->delete('TagDiscussion', array('TagID' => $TagID));
                    $SQL->delete('Tag', array('TagID' => $TagID));

                    $Sender->informMessage(formatString(t('<b>{Name}</b> deleted.'), $Tag));
                    $Sender->jsonTarget("#Tag_{$Tag['TagID']}", null, 'Remove');
                }

                $Sender->render('blank', 'utility', 'dashboard');
                break;
            case 'edit':
                $Sender->setHighlightRoute('settings/tagging');
                $Sender->title(t('Edit Tag'));
                $TagID = val(1, $Sender->RequestArgs);

                // Set the model on the form.
                $TagModel = new TagModel;
                $Sender->Form->setModel($TagModel);
                $Tag = $TagModel->getID($TagID);
                $Sender->Form->setData($Tag);

                // Make sure the form knows which item we are editing.
                $Sender->Form->addHidden('TagID', $TagID);

                if ($Sender->Form->authenticatedPostBack()) {
                    // Make sure the tag is valid
                    $TagData = $Sender->Form->getFormValue('Name');
                    if (!TagModel::validateTag($TagData)) {
                        $Sender->Form->addError('@'.t('ValidateTag', 'Tags cannot contain commas.'));
                    }

                    // Make sure that the tag name is not already in use.
                    if ($TagModel->getWhere(array('TagID <>' => $TagID, 'Name' => $TagData))->numRows() > 0) {
                        $Sender->setData('MergeTagVisible', true);
                        if (!$Sender->Form->getFormValue('MergeTag')) {
                            $Sender->Form->addError('The specified tag name is already in use.');
                        }
                    }

                    if ($Sender->Form->Save()) {
                        $Sender->informMessage(t('Your changes have been saved.'));
                        $Sender->RedirectUrl = url('/settings/tagging');
                    }
                }

                $Sender->render('tags');
                break;
            case 'add':
            default:
                $Sender->setHighlightRoute('settings/tagging');
                $Sender->title('Add Tag');

                // Set the model on the form.
                $TagModel = new TagModel;
                $Sender->Form->setModel($TagModel);

                // Add types if allowed to add tags for it, and not '' or 'tags', which
                // are the same.
                $TagType = Gdn::request()->get('type');
                if (strtolower($TagType) != 'tags' && $TagModel->canAddTagForType($TagType)) {
                    $Sender->Form->addHidden('Type', $TagType, true);
                }

                if ($Sender->Form->authenticatedPostBack()) {
                    // Make sure the tag is valid
                    $TagName = $Sender->Form->getFormValue('Name');
                    if (!TagModel::validateTag($TagName)) {
                        $Sender->Form->addError('@'.t('ValidateTag', 'Tags cannot contain commas.'));
                    }

                    $TagType = $Sender->Form->getFormValue('Type');
                    if (!$TagModel->canAddTagForType($TagType)) {
                        $Sender->Form->addError('@'.t('ValidateTagType', 'That type does not accept manually adding new tags.'));
                    }

                    // Make sure that the tag name is not already in use.
                    if ($TagModel->getWhere(array('Name' => $TagName))->numRows() > 0) {
                        $Sender->Form->addError('The specified tag name is already in use.');
                    }

                    $Saved = $Sender->Form->save();
                    if ($Saved) {
                        $Sender->informMessage(t('Your changes have been saved.'));
                        $Sender->RedirectUrl = url('/settings/tagging');
                    }
                }

                $Sender->render('tags');
            break;
        }
    }

    /**
     * Add the tag endpoint to the discussionController
     *
     * @param DiscussionController $sender
     * @param int $discussionID
     * @throws Exception
     *
     */
    public function discussionController_tag_create($sender, $discussionID, $origin) {
        if (!c('Tagging.Discussions.Enabled')) {
            throw new Exception('Not found', 404);
        }

        if (!filter_var($discussionID, FILTER_VALIDATE_INT)) {
            throw NotFoundException('Discussion');
        }

        $discussion = DiscussionModel::instance()->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion) {
            throw NotFoundException('Discussion');
        }

        $sender->title('Add Tags');

        if ($sender->Form->authenticatedPostBack()) {
            $rawFormTags = $sender->Form->getFormValue('Tags');
            $formTags = TagModel::splitTags($rawFormTags);

            if (!$formTags) {
                $sender->Form->addError('@'.t('No tags provided.'));
            } else {
                // If we're associating with categories
                $categoryID = -1;
                if (c('Vanilla.Tagging.CategorySearch', false)) {
                    $categoryID = val('CategoryID', $discussion, -1);
                }

                // Save the tags to the db.
                TagModel::instance()->saveDiscussion($discussionID, $formTags, 'Tag', $categoryID);

                $sender->informMessage(t('The tags have been added to the discussion.'));
            }
        }

        $sender->render('tag', 'discussion', 'vanilla');
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
        $this->checkAccessToken();
    }

    /**
     * Check the access token.
     */
    private function checkAccessToken() {
        if (empty($_SERVER['HTTP_AUTHORIZATION']) ||
            !stringBeginsWith(Gdn::request()->getPath(), '/api/') ||
            !preg_match('`^Bearer\s+(v[a-z]\.[^\s]+)`i', $_SERVER['HTTP_AUTHORIZATION'], $m)
        ) {
            return;
        }

        $token = $m[1];
        if ($token) {
            $model = new AccessTokenModel();

            try {
                $authRow = $model->verify($token, true);

                Gdn::Session()->start($authRow['UserID'], false, false);
                Gdn::Session()->validateTransientKey(true);
            } catch (\Exception $ex) {
                // Add a psuedo-WWW-Authenticate header. We want the response to know, but don't want to kill everything.
                $msg = $ex->getMessage();
                safeHeader("X-WWW-Authenticate: error=\"invalid_token\", error_description=\"$msg\"");
            }
        }
    }

    /**
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_sendHeaders_handler($sender) {
        $csrfToken = Gdn::request()->post(
            Gdn_Session::CSRF_NAME,
            Gdn::request()->get(
                Gdn_Session::CSRF_NAME,
                Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_X_CSRF_TOKEN')
            )
        );

        if ($csrfToken && Gdn::session()->isValid() && !Gdn::session()->validateTransientKey($csrfToken)) {
            safeHeader('X-CSRF-Token: '.Gdn::session()->transientKey());
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

        // Get the default authentication provider.
        $DefaultProvider = Gdn_AuthenticationProviderModel::getDefault();
        $Sender->EventArguments['Target'] = $Target;
        $Sender->EventArguments['DefaultProvider'] = $DefaultProvider;
        $Handled = false;
        $Sender->EventArguments['Handled'] =& $Handled;

        $Sender->fireEvent('SSO');

        // If an event handler didn't handle the signin then just redirect to the target.
        if (!$Handled) {
            safeRedirect($Target, 302);
        }
    }

    /**
     * Clear user navigation preferences if we can't find the explicit method on the controller.
     *
     * @param Gdn_Controller $sender
     * @param array $args Event arguments. We can expect a 'PathArgs' key here.
     */
    public function gdn_dispatcher_methodNotFound_handler($sender, $args) {
        // If PathArgs is empty, the user hit the root, and we assume they want the index.
        // If not, they got redirected to the root because their controller method was not
        // found. We should clear the user prefs in that case.
        if (!empty($args['PathArgs'])) {
            if (Gdn::session()->isValid()) {
                $uri = Gdn::request()->getRequestArguments('server')['REQUEST_URI'];
                try {
                    $userModel = new UserModel();
                    $userModel->clearSectionNavigationPreference($uri);
                } catch (Exception $ex) {
                    // Nothing
                }
            }
        }
    }

    /**
     *
     *
     * @param SiteNavModule $sender
     */
    public function siteNavModule_init_handler($sender) {

        // GLOBALS

        // Add a link to the community home.
        $sender->addLinkToGlobals(t('Community Home'), '/', 'main.home', '', -100, array('icon' => 'home'), false);
        $sender->addGroupToGlobals('', 'etc', '', 100);
        $sender->addLinkToGlobalsIf(Gdn::session()->isValid() && IsMobile(), t('Full Site'), '/profile/nomobile', 'etc.nomobile', 'js-hijack', 100, array('icon' => 'resize-full'));
        $sender->addLinkToGlobalsIf(Gdn::session()->isValid(), t('Sign Out'), SignOutUrl(), 'etc.signout', '', 100, array('icon' => 'signout'));
        $sender->addLinkToGlobalsIf(!Gdn::session()->isValid(), t('Sign In'), SigninUrl(), 'etc.signin', '', 100, array('icon' => 'signin'));

        // DEFAULTS

        if (!Gdn::session()->isValid()) {
            return;
        }

        $sender->addLinkIf(Gdn::session()->isValid(), t('Profile'), '/profile', 'main.profile', 'profile', 10, array('icon' => 'user'))
            ->addLinkIf('Garden.Activity.View', t('Activity'), '/activity', 'main.activity', 'activity', 10, array('icon' => 'time'));

        // Add the moderation items.
        $sender->addGroup(t('Moderation'), 'moderation', 'moderation', 90);
        if (Gdn::session()->checkPermission('Garden.Users.Approve')) {
            $RoleModel = new RoleModel();
            $applicant_count = (int)$RoleModel->getApplicantCount();
            if ($applicant_count > 0 || true) {
                $sender->addLink(t('Applicants'), '/user/applicants', 'moderation.applicants', 'applicants', array(), array('icon' => 'user', 'badge' => $applicant_count));
            }
        }
        $sender->addLinkIf('Garden.Moderation.Manage', t('Spam Queue'), '/log/spam', 'moderation.spam', 'spam', array(), array('icon' => 'spam'))
            ->addLinkIf('Garden.Settings.Manage', t('Dashboard'), '/settings', 'etc.dashboard', 'dashboard', array(), array('icon' => 'dashboard'));

        $user = Gdn::controller()->data('Profile');
        $user_id = val('UserID', $user);

        //EDIT PROFILE SECTION

        // Users can edit their own profiles and moderators can edit any profile.
        $sender->addLinkToSectionIf(hasEditProfile($user_id), 'EditProfile', t('Profile'), userUrl($user, '', 'edit'), 'main.editprofile', '', array(), array('icon' => 'edit'))
            ->addLinkToSectionIf('Garden.Users.Edit', 'EditProfile', t('Edit Account'), '/user/edit/'.$user_id, 'main.editaccount', 'Popup', array(), array('icon' => 'cog'))
            ->addLinkToSection('EditProfile', t('Back to Profile'), userUrl($user), 'main.profile', '', 100, array('icon' => 'arrow-left'));


        //PROFILE SECTION

        $sender->addLinkToSectionIf(c('Garden.Profile.ShowActivities', true), 'Profile', t('Activity'), userUrl($user, '', 'activity'), 'main.activity', '', array(), array('icon' => 'time'))
            ->addLinkToSectionIf(Gdn::controller()->data('Profile.UserID') == Gdn::session()->UserID, 'Profile', t('Notifications'), userUrl($user, '', 'notifications'), 'main.notifications', '', array(), array('icon' => 'globe', 'badge' => Gdn::controller()->data('Profile.CountNotifications')))
            // Show the invitations if we're using the invite registration method.
            ->addLinkToSectionIf(strcasecmp(c('Garden.Registration.Method'), 'invitation') === 0, 'Profile', t('Invitations'), userUrl($user, '', 'invitations'), 'main.invitations', '', array(), array('icon' => 'ticket'))
            // Users can edit their own profiles and moderators can edit any profile.
            ->addLinkToSectionIf(hasEditProfile($user_id), 'Profile', t('Edit Profile'), userUrl($user, '', 'edit'), 'Profile', 'main.editprofile', '', array(), array('icon' => 'edit'));

    }

    /**
     * After executing /settings/utility/update check if any role permissions have been changed, if not reset all the permissions on the roles.
     *
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
     * Copy a file locally so that it can be manipulated by php.
     *
     * @param Gdn_Upload $sender The upload object doing the manipulation.
     * @param array $args Arguments useful for copying the file.
     * @throws Exception Throws an exception if there was a problem copying the file for local use.
     */
    public function gdn_upload_copyLocal_handler($sender, $args) {
        $parsed = $args['Parsed'];
        if ($parsed['Type'] !== 'static' || $parsed['Domain'] !== 'v') {
            return;
        }

        $remotePath = PATH_ROOT.'/'.$parsed['Name'];

        // Since this is just a temp file we don't want to nest it in a bunch of subfolders.
        $localPath = paths(PATH_UPLOADS, 'tmp-static', str_replace('/', '-', $parsed['Name']));

        // Make sure the destination path exists
        if (!file_exists(dirname($localPath))) {
            mkdir(dirname($localPath), 0777, true);
        }

        // Copy
        copy($remotePath, $localPath);

        $args['Path'] = $localPath;
    }
}
