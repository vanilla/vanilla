<?php
/**
 * VanillaHooks Plugin
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @since 2.0
 * @package Vanilla
 */

/**
 * Vanilla's event handlers.
 */
class VanillaHooks implements Gdn_IPlugin {

    /**
     * Counter rebuilding.
     *
     * @param DbaController $Sender
     */
    public function dbaController_CountJobs_Handler($Sender) {
        $Counts = array(
            'Discussion' => array('CountComments', 'FirstCommentID', 'LastCommentID', 'DateLastComment', 'LastCommentUserID'),
            'Category' => array('CountDiscussions', 'CountComments', 'LastDiscussionID', 'LastCommentID', 'LastDateInserted')
        );

        foreach ($Counts as $Table => $Columns) {
            foreach ($Columns as $Column) {
                $Name = "Recalculate $Table.$Column";
                $Url = "/dba/counts.json?".http_build_query(array('table' => $Table, 'column' => $Column));

                $Sender->Data['Jobs'][$Name] = $Url;
            }
        }
    }

    /**
     * Delete all of the Vanilla related information for a specific user.
     *
     * @since 2.1
     *
     * @param int $UserID The ID of the user to delete.
     * @param array $Options An array of options:
     *  - DeleteMethod: One of delete, wipe, or NULL
     */
    public function deleteUserData($UserID, $Options = array(), &$Data = null) {
        $SQL = Gdn::sql();

        // Remove discussion watch records and drafts.
        $SQL->delete('UserDiscussion', array('UserID' => $UserID));

        Gdn::userModel()->GetDelete('Draft', array('InsertUserID' => $UserID), $Data);

        // Comment deletion depends on method selected
        $DeleteMethod = val('DeleteMethod', $Options, 'delete');
        if ($DeleteMethod == 'delete') {
            // Clear out the last posts to the categories.
            $SQL
                ->update('Category c')
                ->join('Discussion d', 'd.DiscussionID = c.LastDiscussionID')
                ->where('d.InsertUserID', $UserID)
                ->set('c.LastDiscussionID', null)
                ->set('c.LastCommentID', null)
                ->put();

            $SQL
                ->update('Category c')
                ->join('Comment d', 'd.CommentID = c.LastCommentID')
                ->where('d.InsertUserID', $UserID)
                ->set('c.LastDiscussionID', null)
                ->set('c.LastCommentID', null)
                ->put();

            // Grab all of the discussions that the user has engaged in.
            $DiscussionIDs = $SQL
                ->select('DiscussionID')
                ->from('Comment')
                ->where('InsertUserID', $UserID)
                ->groupBy('DiscussionID')
                ->get()->resultArray();
            $DiscussionIDs = consolidateArrayValuesByKey($DiscussionIDs, 'DiscussionID');


            Gdn::userModel()->GetDelete('Comment', array('InsertUserID' => $UserID), $Data);

            // Update the comment counts.
            $CommentCounts = $SQL
                ->select('DiscussionID')
                ->select('CommentID', 'count', 'CountComments')
                ->select('CommentID', 'max', 'LastCommentID')
                ->whereIn('DiscussionID', $DiscussionIDs)
                ->groupBy('DiscussionID')
                ->get('Comment')->resultArray();

            foreach ($CommentCounts as $Row) {
                $SQL->put(
                    'Discussion',
                    array('CountComments' => $Row['CountComments'] + 1, 'LastCommentID' => $Row['LastCommentID']),
                    array('DiscussionID' => $Row['DiscussionID'])
                );
            }

            // Update the last user IDs.
            $SQL->update('Discussion d')
                ->join('Comment c', 'd.LastCommentID = c.CommentID', 'left')
                ->set('d.LastCommentUserID', 'c.InsertUserID', false, false)
                ->set('d.DateLastComment', 'c.DateInserted', false, false)
                ->whereIn('d.DiscussionID', $DiscussionIDs)
                ->put();

            // Update the last posts.
            $Discussions = $SQL
                ->whereIn('DiscussionID', $DiscussionIDs)
                ->where('LastCommentUserID', $UserID)
                ->get('Discussion');

            // Delete the user's dicussions
            Gdn::userModel()->GetDelete('Discussion', array('InsertUserID' => $UserID), $Data);

            // Update the appropriat recent posts in the categories.
            $CategoryModel = new CategoryModel();
            $Categories = $CategoryModel->getWhere(array('LastDiscussionID' => null))->resultArray();
            foreach ($Categories as $Category) {
                $CategoryModel->SetRecentPost($Category['CategoryID']);
            }
        } elseif ($DeleteMethod == 'wipe') {
            // Erase the user's dicussions
            $SQL->update('Discussion')
                ->set('Body', t('The user and all related content has been deleted.'))
                ->set('Format', 'Deleted')
                ->where('InsertUserID', $UserID)
                ->put();

            $SQL->update('Comment')
                ->set('Body', t('The user and all related content has been deleted.'))
                ->set('Format', 'Deleted')
                ->where('InsertUserID', $UserID)
                ->put();
        } else {
            // Leave comments
        }

        // Remove the user's profile information related to this application
        $SQL->update('User')
            ->set(array(
                'CountDiscussions' => 0,
                'CountUnreadDiscussions' => 0,
                'CountComments' => 0,
                'CountDrafts' => 0,
                'CountBookmarks' => 0
            ))
            ->where('UserID', $UserID)
            ->put();
    }

    /**
     * Provide default permissions for roles, based on the value in their Type column.
     *
     * @param PermissionModel $Sender Instance of permission model that fired the event
     */
    public function permissionModel_DefaultPermissions_Handler($Sender) {
        // Guest defaults
        $Sender->AddDefault(
            RoleModel::TYPE_GUEST,
            array(
                'Vanilla.Discussions.View' => 1
            )
        );
        $Sender->AddDefault(
            RoleModel::TYPE_GUEST,
            array(
                'Vanilla.Discussions.View' => 1
            ),
            'Category',
            -1
        );

        // Unconfirmed defaults
        $Sender->AddDefault(
            RoleModel::TYPE_UNCONFIRMED,
            array(
                'Vanilla.Discussions.View' => 1
            )
        );
        $Sender->AddDefault(
            RoleModel::TYPE_UNCONFIRMED,
            array(
                'Vanilla.Discussions.View' => 1
            ),
            'Category',
            -1
        );

        // Applicant defaults
        $Sender->AddDefault(
            RoleModel::TYPE_APPLICANT,
            array(
                'Vanilla.Discussions.View' => 1
            )
        );
        $Sender->AddDefault(
            RoleModel::TYPE_APPLICANT,
            array(
                'Vanilla.Discussions.View' => 1
            ),
            'Category',
            -1
        );

        // Member defaults
        $Sender->AddDefault(
            RoleModel::TYPE_MEMBER,
            array(
                'Vanilla.Discussions.Add' => 1,
                'Vanilla.Discussions.View' => 1,
                'Vanilla.Comments.Add' => 1
            )
        );
        $Sender->AddDefault(
            RoleModel::TYPE_MEMBER,
            array(
                'Vanilla.Discussions.Add' => 1,
                'Vanilla.Discussions.View' => 1,
                'Vanilla.Comments.Add' => 1
            ),
            'Category',
            -1
        );

        // Moderator defaults
        $Sender->AddDefault(
            RoleModel::TYPE_MODERATOR,
            array(
                'Vanilla.Discussions.Add' => 1,
                'Vanilla.Discussions.Edit' => 1,
                'Vanilla.Discussions.Announce' => 1,
                'Vanilla.Discussions.Sink' => 1,
                'Vanilla.Discussions.Close' => 1,
                'Vanilla.Discussions.Delete' => 1,
                'Vanilla.Discussions.View' => 1,
                'Vanilla.Comments.Add' => 1,
                'Vanilla.Comments.Edit' => 1,
                'Vanilla.Comments.Delete' => 1
            )
        );
        $Sender->AddDefault(
            RoleModel::TYPE_MODERATOR,
            array(
                'Vanilla.Discussions.Add' => 1,
                'Vanilla.Discussions.Edit' => 1,
                'Vanilla.Discussions.Announce' => 1,
                'Vanilla.Discussions.Sink' => 1,
                'Vanilla.Discussions.Close' => 1,
                'Vanilla.Discussions.Delete' => 1,
                'Vanilla.Discussions.View' => 1,
                'Vanilla.Comments.Add' => 1,
                'Vanilla.Comments.Edit' => 1,
                'Vanilla.Comments.Delete' => 1
            ),
            'Category',
            -1
        );

        // Administrator defaults
        $Sender->AddDefault(
            RoleModel::TYPE_ADMINISTRATOR,
            array(
                'Vanilla.Discussions.Add' => 1,
                'Vanilla.Discussions.Edit' => 1,
                'Vanilla.Discussions.Announce' => 1,
                'Vanilla.Discussions.Sink' => 1,
                'Vanilla.Discussions.Close' => 1,
                'Vanilla.Discussions.Delete' => 1,
                'Vanilla.Discussions.View' => 1,
                'Vanilla.Comments.Add' => 1,
                'Vanilla.Comments.Edit' => 1,
                'Vanilla.Comments.Delete' => 1
            )
        );
        $Sender->AddDefault(
            RoleModel::TYPE_ADMINISTRATOR,
            array(
                'Vanilla.Discussions.Add' => 1,
                'Vanilla.Discussions.Edit' => 1,
                'Vanilla.Discussions.Announce' => 1,
                'Vanilla.Discussions.Sink' => 1,
                'Vanilla.Discussions.Close' => 1,
                'Vanilla.Discussions.Delete' => 1,
                'Vanilla.Discussions.View' => 1,
                'Vanilla.Comments.Add' => 1,
                'Vanilla.Comments.Edit' => 1,
                'Vanilla.Comments.Delete' => 1
            ),
            'Category',
            -1
        );
    }

    /**
     * Remove Vanilla data when deleting a user.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param UserModel $Sender UserModel.
     */
    public function userModel_BeforeDeleteUser_Handler($Sender) {
        $UserID = val('UserID', $Sender->EventArguments);
        $Options = val('Options', $Sender->EventArguments, array());
        $Options = is_array($Options) ? $Options : array();
        $Content =& $Sender->EventArguments['Content'];

        $this->DeleteUserData($UserID, $Options, $Content);
    }

    /**
     * Check whether a user has access to view discussions in a particular category.
     *
     * @since 2.0.18
     * @example $UserModel->GetCategoryViewPermission($UserID, $CategoryID).
     *
     * @param $Sender UserModel.
     * @return bool Whether user has permission.
     */
    public function userModel_GetCategoryViewPermission_Create($Sender) {
        static $PermissionModel = null;


        $UserID = arrayValue(0, $Sender->EventArguments, '');
        $CategoryID = arrayValue(1, $Sender->EventArguments, '');
        $Permission = val(2, $Sender->EventArguments, 'Vanilla.Discussions.View');
        if ($UserID && $CategoryID) {
            if ($PermissionModel === null) {
                $PermissionModel = new PermissionModel();
            }

            $Category = CategoryModel::categories($CategoryID);
            if ($Category) {
                $PermissionCategoryID = $Category['PermissionCategoryID'];
            } else {
                $PermissionCategoryID = -1;
            }

            $Result = $PermissionModel->GetUserPermissions($UserID, $Permission, 'Category', 'PermissionCategoryID', 'CategoryID', $PermissionCategoryID);
            return (val($Permission, val(0, $Result), false)) ? true : false;
        }
        return false;
    }

    /**
     * Adds 'Discussion' item to menu.
     *
     * 'Base_Render_Before' will trigger before every pageload across apps.
     * If you abuse this hook, Tim with throw a Coke can at your head.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param object $Sender DashboardController.
     */
    public function base_Render_Before($Sender) {
        $Session = Gdn::session();
        if ($Sender->Menu) {
            $Sender->Menu->addLink('Discussions', t('Discussions'), '/discussions', false, array('Standard' => true));
        }
    }

    /**
     * Adds 'Discussions' tab to profiles and adds CSS & JS files to their head.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param object $Sender ProfileController.
     */
    public function profileController_AddProfileTabs_Handler($Sender) {
        if (is_object($Sender->User) && $Sender->User->UserID > 0) {
            $UserID = $Sender->User->UserID;
            // Add the discussion tab
            $DiscussionsLabel = sprite('SpDiscussions').' '.t('Discussions');
            $CommentsLabel = sprite('SpComments').' '.t('Comments');
            if (c('Vanilla.Profile.ShowCounts', true)) {
                $DiscussionsLabel .= '<span class="Aside">'.CountString(GetValueR('User.CountDiscussions', $Sender, null), "/profile/count/discussions?userid=$UserID").'</span>';
                $CommentsLabel .= '<span class="Aside">'.CountString(GetValueR('User.CountComments', $Sender, null), "/profile/count/comments?userid=$UserID").'</span>';
            }
            $Sender->AddProfileTab(t('Discussions'), 'profile/discussions/'.$Sender->User->UserID.'/'.rawurlencode($Sender->User->Name), 'Discussions', $DiscussionsLabel);
            $Sender->AddProfileTab(t('Comments'), 'profile/comments/'.$Sender->User->UserID.'/'.rawurlencode($Sender->User->Name), 'Comments', $CommentsLabel);
            // Add the discussion tab's CSS and Javascript.
            $Sender->addJsFile('jquery.gardenmorepager.js');
            $Sender->addJsFile('discussions.js');
        }
    }

    /**
     * Adds email notification options to profiles.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param ProfileController $Sender
     */
    public function profileController_AfterPreferencesDefined_Handler($Sender) {
        $Sender->Preferences['Notifications']['Email.DiscussionComment'] = t('Notify me when people comment on my discussions.');
        $Sender->Preferences['Notifications']['Email.BookmarkComment'] = t('Notify me when people comment on my bookmarked discussions.');
        $Sender->Preferences['Notifications']['Email.Mention'] = t('Notify me when people mention me.');
        $Sender->Preferences['Notifications']['Email.ParticipateComment'] = t('Notify me when people comment on discussions I\'ve participated in.');


        $Sender->Preferences['Notifications']['Popup.DiscussionComment'] = t('Notify me when people comment on my discussions.');
        $Sender->Preferences['Notifications']['Popup.BookmarkComment'] = t('Notify me when people comment on my bookmarked discussions.');
        $Sender->Preferences['Notifications']['Popup.Mention'] = t('Notify me when people mention me.');
        $Sender->Preferences['Notifications']['Popup.ParticipateComment'] = t('Notify me when people comment on discussions I\'ve participated in.');

//      if (Gdn::session()->checkPermission('Garden.AdvancedNotifications.Allow')) {
//         $Sender->Preferences['Notifications']['Email.NewDiscussion'] = array(t('Notify me when people start new discussions.'), 'Meta');
//         $Sender->Preferences['Notifications']['Email.NewComment'] = array(t('Notify me when people comment on a discussion.'), 'Meta');
////      $Sender->Preferences['Notifications']['Popup.NewDiscussion'] = t('Notify me when people start new discussions.');
//      }

        if (Gdn::session()->checkPermission('Garden.AdvancedNotifications.Allow')) {
            $PostBack = $Sender->Form->isPostBack();
            $Set = array();

            // Add the category definitions to for the view to pick up.
            $DoHeadings = c('Vanilla.Categories.DoHeadings');
            // Grab all of the categories.
            $Categories = array();
            $Prefixes = array('Email.NewDiscussion', 'Popup.NewDiscussion', 'Email.NewComment', 'Popup.NewComment');
            foreach (CategoryModel::categories() as $Category) {
                if (!$Category['PermsDiscussionsView'] || $Category['Depth'] <= 0 || $Category['Depth'] > 2 || $Category['Archived']) {
                    continue;
                }

                $Category['Heading'] = ($DoHeadings && $Category['Depth'] <= 1);
                $Categories[] = $Category;

                if ($PostBack) {
                    foreach ($Prefixes as $Prefix) {
                        $FieldName = "$Prefix.{$Category['CategoryID']}";
                        $Value = $Sender->Form->getFormValue($FieldName, null);
                        if (!$Value) {
                            $Value = null;
                        }
                        $Set[$FieldName] = $Value;
                    }
                }
            }
            $Sender->setData('CategoryNotifications', $Categories);
            if ($PostBack) {
                UserModel::SetMeta($Sender->User->UserID, $Set, 'Preferences.');
            }
        }
    }

    /**
     *
     * @param ProfileController $Sender
     */
    public function profileController_CustomNotificationPreferences_Handler($Sender) {
        if (!$Sender->data('NoEmail') && Gdn::session()->checkPermission('Garden.AdvancedNotifications.Allow')) {
            include $Sender->fetchViewLocation('NotificationPreferences', 'Settings', 'Vanilla');
        }
    }

    /**
     * Add the discussion search to the search.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param object $Sender SearchModel
     */
    public function searchModel_Search_Handler($Sender) {
        $SearchModel = new VanillaSearchModel();
        $SearchModel->Search($Sender);
    }

    /**
     * Load forum information into the BuzzData collection.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param object $Sender SettingsController.
     */
    public function settingsController_DashboardData_Handler($Sender) {
        /*
        $DiscussionModel = new DiscussionModel();
        // Number of Discussions
        $CountDiscussions = $DiscussionModel->getCount();
        $Sender->addDefinition('CountDiscussions', $CountDiscussions);
        $Sender->BuzzData[T('Discussions')] = number_format($CountDiscussions);
        // Number of New Discussions in the last day
        $Sender->BuzzData[T('New discussions in the last day')] = number_format($DiscussionModel->getCount(array('d.DateInserted >=' => Gdn_Format::toDateTime(strtotime('-1 day')))));
        // Number of New Discussions in the last week
        $Sender->BuzzData[T('New discussions in the last week')] = number_format($DiscussionModel->getCount(array('d.DateInserted >=' => Gdn_Format::toDateTime(strtotime('-1 week')))));

        $CommentModel = new CommentModel();
        // Number of Comments
        $CountComments = $CommentModel->getCountWhere();
        $Sender->addDefinition('CountComments', $CountComments);
        $Sender->BuzzData[T('Comments')] = number_format($CountComments);
        // Number of New Comments in the last day
        $Sender->BuzzData[T('New comments in the last day')] = number_format($CommentModel->getCountWhere(array('DateInserted >=' => Gdn_Format::toDateTime(strtotime('-1 day')))));
        // Number of New Comments in the last week
        $Sender->BuzzData[T('New comments in the last week')] = number_format($CommentModel->getCountWhere(array('DateInserted >=' => Gdn_Format::toDateTime(strtotime('-1 week')))));
        */
    }

    /**
     * @param SiteLinkMenuModule $sender
     */
    public function siteNavModule_default_handler($sender) {
        // Grab the default route so that we don't add a link to it twice.
        $home = trim(val('Destination', Gdn::router()->GetRoute('DefaultController')), '/');

        // Add the site discussion links.
        if ($home !== 'categories') {
            $sender->addLink('main.categories', array('text' => t('All Categories', 'Categories'), 'url' => '/categories', 'icon' => icon('th-list'), 'sort' => 1));
        }
        if ($home !== 'discussions') {
            $sender->addLink('main.discussions', array('text' => t('Recent Discussions'), 'url' => '/discussions', 'icon' => icon('discussion'), 'sort' => 1));
        }

        // Add favorites.
        $sender->addGroup('favorites', array('text' => t('Favorites')));

        if (Gdn::session()->isValid()) {
            $sender->addLink('favorites.bookmarks', array('text' => t('My Bookmarks'),
                'url' => '/discussions/bookmarked', 'icon' => icon('star'),
                'badge' => countString(Gdn::session()->User->CountBookmarks, url('/discussions/userbookmarkcount'))));
            $sender->addLink('favorites.discussions', array('text' => t('My Discussions'),
                'url' => '/discussions/mine', 'icon' => icon('discussion'),
                'badge' => countString(Gdn::session()->User->CountDiscussions)));
            $sender->addLink('favorites.drafts', array('text' => t('Drafts'), 'url' => '/drafts',
                'icon' => icon('compose'),
                'badge' => countString(Gdn::session()->User->CountDrafts)));
        }
    }

    /**
     * @param SiteLinkMenuModule $sender
     */
    public function siteNavModule_profile_handler($sender) {
        $user = Gdn::controller()->data('Profile');

        if (!$user) {
            return;
        }

        $user_id = val('UserID', $user);

        $sender->addGroup('posts', array('text' => t('Posts')));

        $sender->addLink('posts.discussions', array('text' => t('Discussions'), 'url' => userUrl($user, '', 'discussions'),
            'icon' => icon('discussion'), 'badge' => countString(val('CountDiscussions', $user), "/profile/count/discussions?userid=$user_id")));

        $sender->addLink('posts.comments', array('text' => t('Comments'), 'url' => userUrl($user, '', 'comments'),
            'icon' => icon('comment'), 'badge' => countString(val('CountComments', $user), "/profile/count/comments?userid=$user_id")));
    }

    /**
     * Creates virtual 'Comments' method in ProfileController.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param ProfileController $Sender ProfileController.
     */
    public function profileController_Comments_Create($Sender, $UserReference = '', $Username = '', $Page = '', $UserID = '') {
        $Sender->editMode(false);
        $View = $Sender->View;
        // Tell the ProfileController what tab to load
        $Sender->getUserInfo($UserReference, $Username, $UserID);
        $Sender->_setBreadcrumbs(t('Comments'), userUrl($Sender->User, '', 'comments'));
        $Sender->SetTabView('Comments', 'profile', 'Discussion', 'Vanilla');

        $PageSize = Gdn::config('Vanilla.Discussions.PerPage', 30);
        list($Offset, $Limit) = offsetLimit($Page, $PageSize);

        $CommentModel = new CommentModel();
        $Comments = $CommentModel->GetByUser2($Sender->User->UserID, $Limit, $Offset, $Sender->Request->get('lid'));
        $TotalRecords = $Offset + $CommentModel->LastCommentCount + 1;

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $Sender->Pager = $PagerFactory->GetPager('MorePager', $Sender);
        $Sender->Pager->MoreCode = 'More Comments';
        $Sender->Pager->LessCode = 'Newer Comments';
        $Sender->Pager->ClientID = 'Pager';
        $Sender->Pager->configure(
            $Offset,
            $Limit,
            $TotalRecords,
            userUrl($Sender->User, '', 'comments').'?page={Page}' //?lid='.$CommentModel->LastCommentID
        );

        // Deliver JSON data if necessary
        if ($Sender->deliveryType() != DELIVERY_TYPE_ALL && $Offset > 0) {
            $Sender->setJson('LessRow', $Sender->Pager->toString('less'));
            $Sender->setJson('MoreRow', $Sender->Pager->toString('more'));
            $Sender->View = 'profilecomments';
        }
        $Sender->setData('Comments', $Comments);

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $Sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show discussion options
        $Sender->ShowOptions = false;

        if ($Sender->Head) {
            $Sender->Head->addTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
        }

        // Render the ProfileController
        $Sender->render();
    }

    /**
     * Creates virtual 'Discussions' method in ProfileController.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param ProfileController $Sender ProfileController.
     */
    public function profileController_Discussions_Create($Sender, $UserReference = '', $Username = '', $Page = '', $UserID = '') {
        $Sender->editMode(false);

        // Tell the ProfileController what tab to load
        $Sender->getUserInfo($UserReference, $Username, $UserID);
        $Sender->_setBreadcrumbs(t('Discussions'), userUrl($Sender->User, '', 'discussions'));
        $Sender->SetTabView('Discussions', 'Profile', 'Discussions', 'Vanilla');
        $Sender->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);

        list($Offset, $Limit) = offsetLimit($Page, Gdn::config('Vanilla.Discussions.PerPage', 30));

        $DiscussionModel = new DiscussionModel();
        $Discussions = $DiscussionModel->GetByUser($Sender->User->UserID, $Limit, $Offset, false, Gdn::session()->UserID);
        $CountDiscussions = $Offset + $DiscussionModel->LastDiscussionCount + 1;
        $Sender->DiscussionData = $Sender->setData('Discussions', $Discussions);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $Sender->Pager = $PagerFactory->GetPager('MorePager', $Sender);
        $Sender->Pager->MoreCode = 'More Discussions';
        $Sender->Pager->LessCode = 'Newer Discussions';
        $Sender->Pager->ClientID = 'Pager';
        $Sender->Pager->configure(
            $Offset,
            $Limit,
            $CountDiscussions,
            userUrl($Sender->User, '', 'discussions').'?page={Page}'
        );

        // Deliver JSON data if necessary
        if ($Sender->deliveryType() != DELIVERY_TYPE_ALL && $Offset > 0) {
            $Sender->setJson('LessRow', $Sender->Pager->toString('less'));
            $Sender->setJson('MoreRow', $Sender->Pager->toString('more'));
            $Sender->View = 'discussions';
        }

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $Sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show discussion options
        $Sender->ShowOptions = false;

        if ($Sender->Head) {
            // These pages offer only duplicate content to search engines and are a bit slow.
            $Sender->Head->addTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
        }

        // Render the ProfileController
        $Sender->render();
    }

    /**
     * Makes sure forum administrators can see the dashboard admin pages.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param object $Sender SettingsController.
     */
    public function settingsController_DefineAdminPermissions_Handler($Sender) {
        if (isset($Sender->RequiredAdminPermissions)) {
            $Sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
        }
    }

    public function gdn_Statistics_Tick_Handler($Sender, $Args) {
        $Path = Gdn::request()->Post('Path');
        $Args = Gdn::request()->Post('Args');
        parse_str($Args, $Args);
        $ResolvedPath = trim(Gdn::request()->Post('ResolvedPath'), '/');
        $ResolvedArgs = @json_decode(Gdn::request()->Post('ResolvedArgs'));
        $DiscussionID = null;
        $DiscussionModel = new DiscussionModel();

//      Gdn::controller()->setData('Path', $Path);
//      Gdn::controller()->setData('Args', $Args);
//      Gdn::controller()->setData('ResolvedPath', $ResolvedPath);
//      Gdn::controller()->setData('ResolvedArgs', $ResolvedArgs);

        // Comment permalink
        if ($ResolvedPath == 'vanilla/discussion/comment') {
            $CommentID = val('CommentID', $ResolvedArgs);
            $CommentModel = new CommentModel();
            $Comment = $CommentModel->getID($CommentID);
            $DiscussionID = val('DiscussionID', $Comment);
        } // Discussion link
        elseif ($ResolvedPath == 'vanilla/discussion/index') {
            $DiscussionID = val('DiscussionID', $ResolvedArgs, null);
        } // Embedded discussion
        elseif ($ResolvedPath == 'vanilla/discussion/embed') {
            $ForeignID = val('vanilla_identifier', $Args);
            if ($ForeignID) {
                // This will be hit a lot so let's try caching it...
                $Key = "DiscussionID.ForeignID.page.$ForeignID";
                $DiscussionID = Gdn::cache()->get($Key);
                if (!$DiscussionID) {
                    $Discussion = $DiscussionModel->GetForeignID($ForeignID, 'page');
                    $DiscussionID = val('DiscussionID', $Discussion);
                    Gdn::cache()->store($Key, $DiscussionID, array(Gdn_Cache::FEATURE_EXPIRY, 1800));
                }
            }
        }

        if ($DiscussionID) {
            $DiscussionModel->AddView($DiscussionID);
        }
    }

    /**
     * Adds items to dashboard menu.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param object $Sender DashboardController.
     */
    public function base_GetAppSettingsMenuItems_Handler($Sender) {
        $Menu = &$Sender->EventArguments['SideMenu'];
        $Menu->addLink('Moderation', t('Flood Control'), 'vanilla/settings/floodcontrol', 'Garden.Settings.Manage', array('class' => 'nav-flood-control'));
        $Menu->addLink('Forum', t('Categories'), 'vanilla/settings/managecategories', 'Garden.Community.Manage', array('class' => 'nav-manage-categories'));
        $Menu->addLink('Forum', t('Advanced'), 'vanilla/settings/advanced', 'Garden.Settings.Manage', array('class' => 'nav-forum-advanced'));
        $Menu->addLink('Forum', t('Blog Comments'), 'dashboard/embed/comments', 'Garden.Settings.Manage', array('class' => 'nav-embed nav-embed-comments'));
        $Menu->addLink('Forum', t('Embed Forum'), 'dashboard/embed/forum', 'Garden.Settings.Manage', array('class' => 'nav-embed nav-embed-site'));
    }

    /**
     * Automatically executed when application is enabled.
     *
     * @since 2.0.0
     * @package Vanilla
     */
    public function setup() {
        $Database = Gdn::database();
        $Config = Gdn::factory(Gdn::AliasConfig);
        $Drop = false; //Gdn::config('Vanilla.Version') === FALSE ? TRUE : FALSE;
        $Explicit = true;

        // Call structure.php to update database
        $Validation = new Gdn_Validation(); // Needed by structure.php to validate permission names
        include(PATH_APPLICATIONS.DS.'vanilla'.DS.'settings'.DS.'structure.php');

        saveToConfig('Routes.DefaultController', 'discussions');
    }
}
