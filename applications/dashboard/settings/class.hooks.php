<?php
/**
 * DashboardHooks class.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

use Garden\Container\Container;
use Garden\Container\Reference;
use Garden\Web\Exception\ClientException;
use Vanilla\Exception\PermissionException;

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
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        $session = Gdn::session();


        if ($sender->MasterView == 'admin') {
            if (val('Form', $sender)) {
                $sender->Form->setStyles('bootstrap');
            }

            $sender->CssClass = htmlspecialchars($sender->CssClass);
            $sections = Gdn_Theme::section(null, 'get');
            if (is_array($sections)) {
                foreach ($sections as $section) {
                    $sender->CssClass .= ' Section-'.$section;
                }
            }

            // Get our plugin nav items.
            $navAdapter = new NestedCollectionAdapter(DashboardNavModule::getDashboardNav());
            $sender->EventArguments['SideMenu'] = $navAdapter;
            $sender->fireEvent('GetAppSettingsMenuItems');

            $sender->removeJsFile('jquery.popup.js');
            $sender->addJsFile('vendors/jquery.checkall.min.js', 'dashboard');
            $sender->addJsFile('buttongroup.js', 'dashboard');
            $sender->addJsFile('dashboard.js', 'dashboard');
            $sender->addJsFile('jquery.expander.js');
            $sender->addJsFile('settings.js', 'dashboard');
            $sender->addJsFile('vendors/tether.min.js', 'dashboard');
            $sender->addJsFile('vendors/bootstrap/util.js', 'dashboard');
            $sender->addJsFile('vendors/drop.min.js', 'dashboard');
            $sender->addJsFile('vendors/moment.min.js', 'dashboard');
            $sender->addJsFile('vendors/daterangepicker.js', 'dashboard');
            $sender->addJsFile('vendors/bootstrap/tooltip.js', 'dashboard');
            $sender->addJsFile('vendors/clipboard.min.js', 'dashboard');
            $sender->addJsFile('vendors/bootstrap/dropdown.js', 'dashboard');
            $sender->addJsFile('vendors/bootstrap/collapse.js', 'dashboard');
            $sender->addJsFile('vendors/bootstrap/modal.js', 'dashboard');
            $sender->addJsFile('vendors/icheck.min.js', 'dashboard');
            $sender->addJsFile('jquery.tablejenga.js', 'dashboard');
            $sender->addJsFile('jquery.fluidfixed.js', 'dashboard');
            $sender->addJsFile('vendors/prettify/prettify.js', 'dashboard');
            $sender->addJsFile('vendors/ace/ace.js', 'dashboard');
            $sender->addJsFile('vendors/ace/ext-searchbox.js', 'dashboard');
            $sender->addCssFile('vendors/tomorrow.css', 'dashboard');
        }

        // Check the statistics.
        if ($sender->deliveryType() == DELIVERY_TYPE_ALL) {
            Gdn::statistics()->check();
        }

        // Inform user of theme previewing
        if ($session->isValid()) {
            $previewThemeFolder = htmlspecialchars($session->getPreference('PreviewThemeFolder', ''));
            $previewMobileThemeFolder = htmlspecialchars($session->getPreference('PreviewMobileThemeFolder', ''));
            $previewThemeName = htmlspecialchars($session->getPreference(
                'PreviewThemeName',
                $previewThemeFolder
            ));
            $previewMobileThemeName = htmlspecialchars($session->getPreference(
                'PreviewMobileThemeName',
                $previewMobileThemeFolder
            ));

            if ($previewThemeFolder != '') {
                $sender->informMessage(
                    sprintf(t('You are previewing the %s desktop theme.'), wrap($previewThemeName, 'em'))
                    .'<div class="PreviewThemeButtons">'
                    .anchor(t('Apply'), 'settings/themes/'.$previewThemeFolder.'/'.$session->transientKey(), 'PreviewThemeButton')
                    .' '.anchor(t('Cancel'), 'settings/cancelpreview/'.$previewThemeFolder.'/'.$session->transientKey(), 'PreviewThemeButton')
                    .'</div>',
                    'DoNotDismiss'
                );
            }

            if ($previewMobileThemeFolder != '') {
                $sender->informMessage(
                    sprintf(t('You are previewing the %s mobile theme.'), wrap($previewMobileThemeName, 'em'))
                    .'<div class="PreviewThemeButtons">'
                    .anchor(t('Apply'), 'settings/mobilethemes/'.$previewMobileThemeFolder.'/'.$session->transientKey(), 'PreviewThemeButton')
                    .' '.anchor(t('Cancel'), 'settings/cancelpreview/'.$previewMobileThemeFolder.'/'.$session->transientKey(), 'PreviewThemeButton')
                    .'</div>',
                    'DoNotDismiss'
                );
            }
        }


        if ($session->isValid()) {
            $confirmed = val('Confirmed', Gdn::session()->User, true);
            if (UserModel::requireConfirmEmail() && !$confirmed) {
                $message = formatString(t('You need to confirm your email address.', 'You need to confirm your email address. Click <a href="{/entry/emailconfirmrequest,url}">here</a> to resend the confirmation email.'));
                $sender->informMessage($message, '');
            }
        }

        // Add Message Modules (if necessary)
        $messageCache = Gdn::config('Garden.Messages.Cache', []);
        $location = $sender->Application.'/'.substr($sender->ControllerName, 0, -10).'/'.$sender->RequestMethod;
        $exceptions = ['[Base]'];

        if (in_array($sender->MasterView, ['', 'default'])) {
            $exceptions[] = '[NonAdmin]';
        }

        // SignIn popup is a special case
        $signInOnly = ($sender->deliveryType() == DELIVERY_TYPE_VIEW && $location == 'Dashboard/entry/signin');
        if ($signInOnly) {
            $exceptions = [];
        }

        if ($sender->MasterView != 'admin' && !$sender->data('_NoMessages') && (val('MessagesLoaded', $sender) != '1' && $sender->MasterView != 'empty' && arrayInArray($exceptions, $messageCache, false) || inArrayI($location, $messageCache))) {
            $messageModel = new MessageModel();
            $messageData = $messageModel->getMessagesForLocation($location, $exceptions, $sender->data('Category.CategoryID'));
            foreach ($messageData as $message) {
                $messageModule = new MessageModule($sender, $message);
                if ($signInOnly) { // Insert special messages even in SignIn popup
                    echo $messageModule;
                } elseif ($sender->deliveryType() == DELIVERY_TYPE_ALL)
                    $sender->addModule($messageModule);
            }
            $sender->MessagesLoaded = '1'; // Fixes a bug where render gets called more than once and messages are loaded/displayed redundantly.
        }

        if ($sender->deliveryType() == DELIVERY_TYPE_ALL) {
            $gdn_Statistics = Gdn::factory('Statistics');
            $gdn_Statistics->check($sender);
        }

        // Allow forum embedding
        if ($embed = c('Garden.Embed.Allow')) {
            // Record the remote url where the forum is being embedded.
            $remoteUrl = c('Garden.Embed.RemoteUrl');
            if (!$remoteUrl) {
                $remoteUrl = getIncomingValue('remote');
                if ($remoteUrl) {
                    saveToConfig('Garden.Embed.RemoteUrl', $remoteUrl);
                }
            }
            if ($remoteUrl) {
                $sender->addDefinition('RemoteUrl', $remoteUrl);
            }
            if ($remoteUrlFormat = c('Garden.Embed.RemoteUrlFormat')) {
                $sender->addDefinition('RemoteUrlFormat', $remoteUrlFormat);
            }

            // Force embedding?
            if (!isSearchEngine() && strtolower($sender->ControllerName) != 'entry') {
                if (isMobile()) {
                    $forceEmbedForum = c('Garden.Embed.ForceMobile') ? '1' : '0';
                } else {
                    $forceEmbedForum = c('Garden.Embed.ForceForum') ? '1' : '0';
                }

                $sender->addDefinition('ForceEmbedForum', $forceEmbedForum);
                $sender->addDefinition('ForceEmbedDashboard', c('Garden.Embed.ForceDashboard') ? '1' : '0');
            }

            $sender->addDefinition('Path', Gdn::request()->path());

            $get = Gdn::request()->get();
            unset($get['p']); // kludge for old index.php?p=/path
            $sender->addDefinition('Query', http_build_query($get));
            // $Sender->addDefinition('MasterView', $Sender->MasterView);
            $sender->addDefinition('InDashboard', $sender->MasterView == 'admin' ? '1' : '0');

            if ($embed === 2) {
                $sender->addJsFile('vanilla.embed.local.js');
            } else {
                $sender->addJsFile('embed_local.js');
            }
        } else {
            $sender->setHeader('X-Frame-Options', 'SAMEORIGIN');
        }


        // Allow return to mobile site
        $forceNoMobile = val('X-UA-Device-Force', $_COOKIE);
        if ($forceNoMobile === 'desktop') {
            $sender->addAsset('Foot', wrap(anchor(t('Back to Mobile Site'), '/profile/nomobile/1', 'js-hijack'), 'div'), 'MobileLink');
        }

        // Allow global translation of TagHint
        if (c('Tagging.Discussions.Enabled')) {
            $sender->addDefinition('TaggingAdd', Gdn::session()->checkPermission('Vanilla.Tagging.Add'));
            $sender->addDefinition('TaggingSearchUrl', Gdn::request()->url('tags/search'));
            $sender->addDefinition('MaxTagsAllowed', c('Vanilla.Tagging.Max', 5));
            $sender->addDefinition('TagHint', t('TagHint', 'Start to type...'));
        }



        // Add symbols.
        if ($sender->deliveryMethod() === DELIVERY_METHOD_XHTML) {
            $sender->addAsset('Symbols', $sender->fetchView('symbols', '', 'Dashboard'));
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

            ->addGroup(t('Connections'), 'connect', '', ['after' => 'reputation'])
            ->addLinkIf('Garden.Settings.Manage', t('Social Connect', 'Social Media'), '/social/manage', 'connect.social', '', $sort)

            ->addGroup(t('Addons'), 'add-ons', '', ['after' => 'connect'])
            ->addLinkIf('Garden.Settings.Manage', t('Plugins'), '/dashboard/settings/plugins', 'add-ons.plugins', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Applications'), '/dashboard/settings/applications', 'add-ons.applications', '', $sort)

            ->addGroup(t('Technical'), 'site-settings', '', ['after' => 'reputation'])
            ->addLinkIf('Garden.Settings.Manage', t('Locales'), '/dashboard/settings/locales', 'site-settings.locales', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Outgoing Email'), '/dashboard/settings/email', 'site-settings.email', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Security'), '/dashboard/settings/security', 'site-settings.security', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Routes'), '/dashboard/routes', 'site-settings.routes', '', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Statistics'), '/dashboard/statistics', 'site-settings.statistics', '', $sort)

            ->addGroupIf('Garden.Settings.Manage', t('Forum Data'), 'forum-data', '', ['after' => 'site-settings'])
            ->addLinkIf(
                $session->checkPermission('Garden.Import'),
                t('Import'),
                '/dashboard/import',
                'forum-data.import',
                '',
                $sort
            );
    }

    /**
     * Aggressively prompt users to upgrade PHP version.
     *
     * @param $sender
     */
    public function settingsController_render_before($sender) {
        // Set this in your config to dismiss our upgrade warnings. Not recommended.
        if (c('Vanilla.WarnedMeToUpgrade') === 'PHP 7.0') {
            return;
        }

        $mysqlVersion = gdn::sql()->version();
        if (version_compare($mysqlVersion, '5.6') < 0) {
            $upgradeMessage = ['Content' => 'We recommend using at least <b>MySQL 5.7</b> or <b>MariaDB 10.2</b>. Version '.htmlspecialchars($mysqlVersion).' will not support all upcoming Vanilla features.', 'AssetTarget' => 'Content', 'CssClass' => 'InfoMessage'];
            $messageModule = new MessageModule($sender, $upgradeMessage);
            $sender->addModule($messageModule);
        }
    }

    /**
     * List all tags and allow searching
     *
     * @param SettingsController $sender
     */
    public function settingsController_tagging_create($sender, $search = null, $type = null, $page = null) {
        $sender->permission('Garden.Settings.Manage');

        $sender->title('Tagging');
        $sender->setHighlightRoute('settings/tagging');
        $sQL = Gdn::sql();

        /** @var Gdn_Form $form */
        $form = $sender->Form;

        if ($form->authenticatedPostBack()) {
            $formValue = (bool)$form->getFormValue('Tagging.Discussions.Enabled');
            saveToConfig('Tagging.Discussions.Enabled', $formValue);
        }

        // Get all tag types
        $tagModel = TagModel::instance();
        $tagTypes = $tagModel->getTagTypes();


        list($offset, $limit) = offsetLimit($page, 100);
        $sender->setData('_Limit', $limit);

        if ($search) {
            $sQL->like('Name', $search, 'right');
        }

        $queryType = $type;

        if (strtolower($type) == 'all' || $search || $type === null) {
            $queryType = false;
            $type = '';
        }

        // This type doesn't actually exist, but it will represent the blank types in the column.
        if (strtolower($type) == 'tags') {
            $queryType = '';
        }

        if (!$search && ($queryType !== false)) {
            $sQL->where('Type', $queryType);
        }

        $tagTypes = array_change_key_case($tagTypes, CASE_LOWER);

        // Store type for view
        $tagType = !empty($type) ? $type : 'All';
        $sender->setData('_TagType', $tagType);

        // Store tag types
        $sender->setData('_TagTypes', $tagTypes);

        // Determine if new tags can be added for the current type.
        $canAddTags = (!empty($tagTypes[$type]['addtag']) && $tagTypes[$type]['addtag']) ? 1 : 0;
        $canAddTags &= checkPermission('Vanilla.Tagging.Add');

        $sender->setData('_CanAddTags', $canAddTags);

        $data = $sQL
            ->select('t.*')
            ->from('Tag t')
            ->orderBy('t.CountDiscussions', 'desc')
            ->limit($limit, $offset)
            ->get()->resultArray();

        $sender->setData('Tags', $data);

        if ($search) {
            $sQL->like('Name', $search, 'right');
        }

        // Make sure search uses its own search type, so results appear in their own tab.
        $sender->Form->Action = url('/settings/tagging/?type='.$tagType);

        // Search results pagination will mess up a bit, so don't provide a type in the count.
        $recordCountWhere = ['Type' => $queryType];
        if ($queryType === false) {
            $recordCountWhere = [];
        }
        if ($search) {
            $recordCountWhere = [];
        }

        $sender->setData('RecordCount', $sQL->getCount('Tag', $recordCountWhere));
        $sender->render('tagging');
    }

    /**
     * Add the tags endpoint to the settingsController
     *
     * @param SettingsController $sender
     * @param string $action
     *
     */
    public function settingsController_tags_create($sender, $action) {
        $sender->permission('Garden.Settings.Manage');

        switch($action) {
            case 'delete':
                $tagID = val(1, $sender->RequestArgs);
                $tagModel = new TagModel();
                $tag = $tagModel->getID($tagID, DATASET_TYPE_ARRAY);

                if ($sender->Form->authenticatedPostBack()) {
                    // Delete tag & tag relations.
                    $sQL = Gdn::sql();
                    $sQL->delete('TagDiscussion', ['TagID' => $tagID]);
                    $sQL->delete('Tag', ['TagID' => $tagID]);

                    $sender->informMessage(formatString(t('<b>{Name}</b> deleted.'), $tag));
                    $sender->jsonTarget("#Tag_{$tag['TagID']}", null, 'Remove');
                }

                $sender->render('blank', 'utility', 'dashboard');
                break;
            case 'edit':
                $sender->setHighlightRoute('settings/tagging');
                $sender->title(t('Edit Tag'));
                $tagID = val(1, $sender->RequestArgs);

                // Set the model on the form.
                $tagModel = new TagModel;
                $sender->Form->setModel($tagModel);
                $tag = $tagModel->getID($tagID);
                $sender->Form->setData($tag);

                // Make sure the form knows which item we are editing.
                $sender->Form->addHidden('TagID', $tagID);

                if ($sender->Form->authenticatedPostBack()) {
                    // Make sure the tag is valid
                    $tagData = $sender->Form->getFormValue('Name');
                    if (!TagModel::validateTag($tagData)) {
                        $sender->Form->addError('@'.t('ValidateTag', 'Tags cannot contain commas.'));
                    }

                    // Make sure that the tag name is not already in use.
                    if ($tagModel->getWhere(['TagID <>' => $tagID, 'Name' => $tagData])->numRows() > 0) {
                        $sender->setData('MergeTagVisible', true);
                        if (!$sender->Form->getFormValue('MergeTag')) {
                            $sender->Form->addError('The specified tag name is already in use.');
                        }
                    }

                    if ($sender->Form->save()) {
                        $sender->informMessage(t('Your changes have been saved.'));
                        $sender->setRedirectTo('/settings/tagging');
                    }
                }

                $sender->render('tags');
                break;
            case 'add':
            default:
                $sender->setHighlightRoute('settings/tagging');
                $sender->title('Add Tag');

                // Set the model on the form.
                $tagModel = new TagModel;
                $sender->Form->setModel($tagModel);

                // Add types if allowed to add tags for it, and not '' or 'tags', which
                // are the same.
                $tagType = Gdn::request()->get('type');
                if (strtolower($tagType) != 'tags' && $tagModel->canAddTagForType($tagType)) {
                    $sender->Form->addHidden('Type', $tagType, true);
                }

                if ($sender->Form->authenticatedPostBack()) {
                    // Make sure the tag is valid
                    $tagName = $sender->Form->getFormValue('Name');
                    if (!TagModel::validateTag($tagName)) {
                        $sender->Form->addError('@'.t('ValidateTag', 'Tags cannot contain commas.'));
                    }

                    $tagType = $sender->Form->getFormValue('Type');
                    if (!$tagModel->canAddTagForType($tagType)) {
                        $sender->Form->addError('@'.t('ValidateTagType', 'That type does not accept manually adding new tags.'));
                    }

                    // Make sure that the tag name is not already in use.
                    if ($tagModel->getWhere(['Name' => $tagName])->numRows() > 0) {
                        $sender->Form->addError('The specified tag name is already in use.');
                    }

                    $saved = $sender->Form->save();
                    if ($saved) {
                        $sender->informMessage(t('Your changes have been saved.'));
                        $sender->setRedirectTo('/settings/tagging');
                    }
                }

                $sender->render('tags');
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
            throw notFoundException('Discussion');
        }

        $discussion = DiscussionModel::instance()->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion) {
            throw notFoundException('Discussion');
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
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {
        safeHeader('P3P: CP="CAO PSA OUR"', true);

        if ($sSO = Gdn::request()->get('sso')) {
            saveToConfig('Garden.Registration.SendConnectEmail', false, false);

            $deliveryMethod = $sender->getDeliveryMethod(Gdn::request());
            $isApi = $deliveryMethod === DELIVERY_METHOD_JSON;

            $userID = false;
            try {
                $currentUserID = Gdn::session()->UserID;
                $userID = Gdn::userModel()->sso($sSO);
            } catch (Exception $ex) {
                trace($ex, TRACE_ERROR);
            }

            if ($userID) {
                Gdn::session()->start($userID, !$isApi, !$isApi);
                if ($isApi) {
                    Gdn::session()->validateTransientKey(true);
                }

                if ($userID != $currentUserID) {
                    Gdn::userModel()->fireEvent('AfterSignIn');
                }
            } else {
                // There was some sort of error. Let's print that out.
                foreach (Gdn::userModel()->Validation->resultsArray() as $msg) {
                    trace($msg, TRACE_ERROR);
                }
                Gdn::userModel()->Validation->reset();
            }

            // Let's redirect to the same url but without the sso parameter to be sure there will be
            // no leak via the Referer field.
            $deliveryType = $sender->getDeliveryType($deliveryMethod);
            if (!$isApi && !Gdn::request()->isPostBack() && $deliveryType !== DELIVERY_TYPE_DATA) {
                $url = trim(preg_replace('#(\?.*)sso=[^&]*&?(.*)$#', '$1$2', Gdn::request()->pathAndQuery()), '&');
                redirectTo($url);
            }
        }
        $this->checkAccessToken();
    }

    /**
     * Check to see if a user is banned.
     *
     * @throws Exception if the user is banned.
     */
    public function base_afterSignIn_handler() {
        if (!Gdn::session()->isValid()) {
            if ($ban = Gdn::session()->getPermissions()->getBan()) {
                throw new ClientException($ban['msg'], 401, $ban);
            } else {
                if (!Gdn::session()->getPermissions()->has('Garden.SignIn.Allow')) {
                    throw new PermissionException('Garden.SignIn.Allow');
                } else {
                    throw new ClientException('The session could not be started', 401);
                }
            }
        }
    }

    /**
     * Check the access token.
     */
    private function checkAccessToken() {
        if (!stringBeginsWith(Gdn::request()->getPath(), '/api/')) {
            return;
        }

        $hasAuthHeader = (!empty($_SERVER['HTTP_AUTHORIZATION']) && preg_match('`^Bearer\s+(v[a-z]\.[^\s]+)`i', $_SERVER['HTTP_AUTHORIZATION'], $m));
        $hasTokenParam = !empty($_GET['access_token']);
        if (!$hasAuthHeader && !$hasTokenParam) {
            return;
        }

        $token = empty($_GET['access_token']) ? $m[1] : $_GET['access_token'];
        if ($token) {
            $model = new AccessTokenModel();

            try {
                $authRow = $model->verify($token, true);

                Gdn::session()->start($authRow['UserID'], false, false);
                Gdn::session()->validateTransientKey(true);
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
     * @param RootController $sender
     * @param string $target The url to redirect to after sso.
     */
    public function rootController_sso_create($sender, $target = '') {
        if (!$target) {
            $target = $sender->Request->get('redirect');
            if (!$target) {
                $target = '/';
            }
        }

        // Get the default authentication provider.
        $defaultProvider = Gdn_AuthenticationProviderModel::getDefault();
        $sender->EventArguments['Target'] = $target;
        $sender->EventArguments['DefaultProvider'] = $defaultProvider;
        $handled = false;
        $sender->EventArguments['Handled'] =& $handled;

        $sender->fireEvent('SSO');

        // If an event handler didn't handle the signin then just redirect to the target.
        if (!$handled) {
            redirectTo($target);
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
        $sender->addLinkToGlobals(t('Community Home'), '/', 'main.home', '', -100, ['icon' => 'home'], false);
        $sender->addGroupToGlobals('', 'etc', '', 100);
        $sender->addLinkToGlobalsIf(Gdn::session()->isValid() && isMobile(), t('Full Site'), '/profile/nomobile', 'etc.nomobile', 'js-hijack', 100, ['icon' => 'resize-full']);
        $sender->addLinkToGlobalsIf(Gdn::session()->isValid(), t('Sign Out'), signOutUrl(), 'etc.signout', '', 100, ['icon' => 'signout']);
        $sender->addLinkToGlobalsIf(!Gdn::session()->isValid(), t('Sign In'), signinUrl(), 'etc.signin', '', 100, ['icon' => 'signin']);

        // DEFAULTS

        if (!Gdn::session()->isValid()) {
            return;
        }

        $sender->addLinkIf(Gdn::session()->isValid(), t('Profile'), '/profile', 'main.profile', 'profile', 10, ['icon' => 'user'])
            ->addLinkIf('Garden.Activity.View', t('Activity'), '/activity', 'main.activity', 'activity', 10, ['icon' => 'time']);

        // Add the moderation items.
        $sender->addGroup(t('Moderation'), 'moderation', 'moderation', 90);
        if (Gdn::session()->checkPermission('Garden.Users.Approve')) {
            $roleModel = new RoleModel();
            $applicant_count = (int)$roleModel->getApplicantCount();
            if ($applicant_count > 0 || true) {
                $sender->addLink(t('Applicants'), '/user/applicants', 'moderation.applicants', 'applicants', [], ['icon' => 'user', 'badge' => $applicant_count]);
            }
        }
        $sender->addLinkIf('Garden.Moderation.Manage', t('Spam Queue'), '/log/spam', 'moderation.spam', 'spam', [], ['icon' => 'spam'])
            ->addLinkIf('Garden.Settings.Manage', t('Dashboard'), '/settings', 'etc.dashboard', 'dashboard', [], ['icon' => 'dashboard']);

        $user = Gdn::controller()->data('Profile');
        $user_id = val('UserID', $user);

        //EDIT PROFILE SECTION

        // Users can edit their own profiles and moderators can edit any profile.
        $sender->addLinkToSectionIf(hasEditProfile($user_id), 'EditProfile', t('Profile'), userUrl($user, '', 'edit'), 'main.editprofile', '', [], ['icon' => 'edit'])
            ->addLinkToSectionIf('Garden.Users.Edit', 'EditProfile', t('Edit Account'), '/user/edit/'.$user_id, 'main.editaccount', 'Popup', [], ['icon' => 'cog'])
            ->addLinkToSection('EditProfile', t('Back to Profile'), userUrl($user), 'main.profile', '', 100, ['icon' => 'arrow-left']);


        //PROFILE SECTION

        $sender->addLinkToSectionIf(c('Garden.Profile.ShowActivities', true), 'Profile', t('Activity'), userUrl($user, '', 'activity'), 'main.activity', '', [], ['icon' => 'time'])
            ->addLinkToSectionIf(Gdn::controller()->data('Profile.UserID') == Gdn::session()->UserID, 'Profile', t('Notifications'), userUrl($user, '', 'notifications'), 'main.notifications', '', [], ['icon' => 'globe', 'badge' => Gdn::controller()->data('Profile.CountNotifications')])
            // Show the invitations if we're using the invite registration method.
            ->addLinkToSectionIf(strcasecmp(c('Garden.Registration.Method'), 'invitation') === 0, 'Profile', t('Invitations'), userUrl($user, '', 'invitations'), 'main.invitations', '', [], ['icon' => 'ticket'])
            // Users can edit their own profiles and moderators can edit any profile.
            ->addLinkToSectionIf(hasEditProfile($user_id), 'Profile', t('Edit Profile'), userUrl($user, '', 'edit'), 'Profile', 'main.editprofile', '', [], ['icon' => 'edit']);

    }

    /**
     * After executing /settings/utility/update check if any role permissions have been changed, if not reset all the permissions on the roles.
     *
     * @param $sender
     */
    public function updateModel_afterStructure_handler($sender) {
        // Only setup default permissions if no role permissions are set.
        $hasPermissions = Gdn::sql()->getWhere('Permission', ['RoleID >' => 0])->firstRow(DATASET_TYPE_ARRAY);
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
