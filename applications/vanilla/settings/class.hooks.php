<?php
/**
 * VanillaHooks Plugin
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
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
    public function dbaController_countJobs_handler($Sender) {
        $Counts = [
            'Discussion' => ['CountComments', 'FirstCommentID', 'LastCommentID', 'DateLastComment', 'LastCommentUserID'],
            'Category' => ['CountDiscussions', 'CountAllDiscussions', 'CountComments', 'CountAllComments', 'LastDiscussionID', 'LastCommentID', 'LastDateInserted'],
            'Tag' => ['table' => 'Tag', 'column' => 'CountDiscussions'],
        ];

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
     * @param int $userID The ID of the user to delete.
     * @param array $options An array of options:
     *  - DeleteMethod: One of delete, wipe, or NULL
     */
    public function deleteUserData($userID, $options = array(), &$data = null) {
        $sql = Gdn::sql();

        // Remove discussion watch records and drafts.
        $sql->delete('UserDiscussion', array('UserID' => $userID));

        Gdn::userModel()->getDelete('Draft', array('InsertUserID' => $userID), $data);

        // Comment deletion depends on method selected
        $deleteMethod = val('DeleteMethod', $options, 'delete');
        if ($deleteMethod == 'delete') {
            // Get a list of category IDs that has this user as the most recent poster.
            $discussionCats = $sql
                ->select('cat.CategoryID')
                ->from('Category cat')
                ->join('Discussion d', 'd.DiscussionID = cat.LastDiscussionID')
                ->where('d.InsertUserID', $userID)
                ->get()->resultArray();

            $commentCats = $sql
                ->select('cat.CategoryID')
                ->from('Category cat')
                ->join('Comment c', 'c.CommentID = cat.LastCommentID')
                ->where('c.InsertUserID', $userID)
                ->get()->resultArray();

            $categoryIDs = array_unique(array_merge(array_column($discussionCats, 'CategoryID'), array_column($commentCats, 'CategoryID')));

            // Grab all of the discussions that the user has engaged in.
            $discussionIDs = $sql
                ->select('DiscussionID')
                ->from('Comment')
                ->where('InsertUserID', $userID)
                ->groupBy('DiscussionID')
                ->get()->resultArray();
            $discussionIDs = array_column($discussionIDs, 'DiscussionID');

            Gdn::userModel()->getDelete('Comment', array('InsertUserID' => $userID), $data);

            // Update the comment counts.
            $commentCounts = $sql
                ->select('DiscussionID')
                ->select('CommentID', 'count', 'CountComments')
                ->select('CommentID', 'max', 'LastCommentID')
                ->whereIn('DiscussionID', $discussionIDs)
                ->groupBy('DiscussionID')
                ->get('Comment')->resultArray();

            foreach ($commentCounts as $row) {
                $sql->put(
                    'Discussion',
                    array('CountComments' => $row['CountComments'] + 1, 'LastCommentID' => $row['LastCommentID']),
                    array('DiscussionID' => $row['DiscussionID'])
                );
            }

            // Update the last user IDs.
            $sql->update('Discussion d')
                ->join('Comment c', 'd.LastCommentID = c.CommentID', 'left')
                ->set('d.LastCommentUserID', 'c.InsertUserID', false, false)
                ->set('d.DateLastComment', 'coalesce(c.DateInserted, d.DateInserted)', false, false)
                ->whereIn('d.DiscussionID', $discussionIDs)
                ->put();

            // Update the last posts.
            $discussions = $sql
                ->whereIn('DiscussionID', $discussionIDs)
                ->where('LastCommentUserID', $userID)
                ->get('Discussion');

            // Delete the user's discussions.
            Gdn::userModel()->getDelete('Discussion', array('InsertUserID' => $userID), $data);

            // Update the appropriate recent posts in the categories.
            $categoryModel = new CategoryModel();
            foreach ($categoryIDs as $categoryID) {
                $categoryModel->setRecentPost($categoryID);
            }
        } elseif ($deleteMethod == 'wipe') {
            // Erase the user's discussions.
            $sql->update('Discussion')
                ->set('Body', t('The user and all related content has been deleted.'))
                ->set('Format', 'Deleted')
                ->where('InsertUserID', $userID)
                ->put();

            $sql->update('Comment')
                ->set('Body', t('The user and all related content has been deleted.'))
                ->set('Format', 'Deleted')
                ->where('InsertUserID', $userID)
                ->put();
        } else {
            // Leave comments
        }

        // Remove the user's profile information related to this application
        $sql->update('User')
            ->set(array(
                'CountDiscussions' => 0,
                'CountUnreadDiscussions' => 0,
                'CountComments' => 0,
                'CountDrafts' => 0,
                'CountBookmarks' => 0
            ))
            ->where('UserID', $userID)
            ->put();
    }

    /**
     * Add tag data to discussions.
     *
     * @param DiscussionController $sender
     */
    public function discussionController_render_before($sender) {
        $discussionID = $sender->data('Discussion.DiscussionID');
        if ($discussionID) {
            // Get the tags on this discussion.
            $tags = TagModel::instance()->getDiscussionTags($discussionID, TagModel::IX_EXTENDED);

            foreach ($tags as $key => $value) {
                $sender->setData('Discussion.'.$key, $value);
            }
        }
    }

    /**
     *
     *
     * @param DiscussionController $sender
     */
    public function discussionController_beforeCommentBody_handler($sender) {
        Gdn::regarding()->beforeCommentBody($sender);
    }

    /**
     * Show tags after discussion body.
     *
     * @param DiscussionController $Sender
     */
    public function discussionController_afterDiscussionBody_handler($Sender) {
        /*  */
        // Allow disabling of inline tags.
        if (!c('Plugins.Tagging.DisableInline', false)) {
            if (!property_exists($Sender->EventArguments['Object'], 'CommentID')) {
                $DiscussionID = property_exists($Sender, 'DiscussionID') ? $Sender->DiscussionID : 0;

                if (!$DiscussionID) {
                    return;
                }

                $TagModule = new TagModule($Sender);
                echo $TagModule->inlineDisplay();
            }
        }
    }

    /**
     * Validate tags when saving a discussion.
     *
     * @param DiscussionModel $Sender
     * @param array $Args
     */
    public function discussionModel_beforeSaveDiscussion_handler($Sender, $Args) {
        // Allow an addon to set disallowed tag names.
        $reservedTags = [];
        $Sender->EventArguments['ReservedTags'] = &$reservedTags;
        $Sender->fireEvent('ReservedTags');

        // Set some tagging requirements.
        $TagsString = trim(strtolower(valr('FormPostValues.Tags', $Args, '')));
        if (stringIsNullOrEmpty($TagsString) && c('Plugins.Tagging.Required')) {
            $Sender->Validation->addValidationResult('Tags', 'You must specify at least one tag.');
        } else {
            // Break apart our tags and lowercase them all for comparisons.
            $Tags = TagModel::splitTags($TagsString);
            $Tags = array_map('strtolower', $Tags);
            $reservedTags = array_map('strtolower', $reservedTags);
            $maxTags = c('Plugin.Tagging.Max', 5);

            // Validate our tags.
            if ($reservedTags = array_intersect($Tags, $reservedTags)) {
                $names = implode(', ', $reservedTags);
                $Sender->Validation->addValidationResult('Tags', '@'.sprintf(t('These tags are reserved and cannot be used: %s'), $names));
            }
            if (!TagModel::validateTags($Tags)) {
                $Sender->Validation->addValidationResult('Tags', '@'.t('ValidateTag', 'Tags cannot contain commas.'));
            }
            if (count($Tags) > $maxTags) {
                $Sender->Validation->addValidationResult('Tags', '@'.sprintf(t('You can only specify up to %s tags.'), $maxTags));
            }
        }
    }

    /**
     * Save tags when saving a discussion.
     *
     * @param DiscussionModel $Sender
     */
    public function discussionModel_afterSaveDiscussion_handler($Sender) {
        $FormPostValues = val('FormPostValues', $Sender->EventArguments, array());
        $DiscussionID = val('DiscussionID', $Sender->EventArguments, 0);
        $CategoryID = valr('Fields.CategoryID', $Sender->EventArguments, 0);
        $RawFormTags = val('Tags', $FormPostValues, '');
        $FormTags = TagModel::splitTags($RawFormTags);

        // If we're associating with categories
        $CategorySearch = c('Plugins.Tagging.CategorySearch', false);
        if ($CategorySearch) {
            $CategoryID = val('CategoryID', $FormPostValues, false);
        }

        // Let plugins have their information getting saved.
        $Types = [''];

        // We fire as TaggingPlugin since this code was taken from the old TaggingPlugin and we do not
        // want to break any hooks
        Gdn::pluginManager()->fireAs('TaggingPlugin')->fireEvent('SaveDiscussion', [
            'Data' => $FormPostValues,
            'Tags' => &$FormTags,
            'Types' => &$Types,
            'CategoryID' => $CategoryID,
        ]);

        // Save the tags to the db.
        TagModel::instance()->saveDiscussion($DiscussionID, $FormTags, $Types, $CategoryID);
    }

    /**
     * Handle tag association deletion when a discussion is deleted.
     *
     * @param $Sender
     * @throws Exception
     */
    public function discussionModel_deleteDiscussion_handler($Sender) {
        // Get discussionID that is being deleted
        $DiscussionID = $Sender->EventArguments['DiscussionID'];

        // Get List of tags to reduce count for
        $TagDataSet = Gdn::sql()->select('TagID')
            ->from('TagDiscussion')
            ->where('DiscussionID', $DiscussionID)
            ->get()->resultArray();

        $RemovedTagIDs = array_column($TagDataSet, 'TagID');

        // Check if there are even any tags to delete
        if (count($RemovedTagIDs) > 0) {
            // Step 1: Reduce count
            Gdn::sql()
                ->update('Tag')
                ->set('CountDiscussions', 'CountDiscussions - 1', false)
                ->whereIn('TagID', $RemovedTagIDs)
                ->put();

            // Step 2: Delete mapping data between discussion and tag (tagdiscussion table)
            $Sender->SQL->where('DiscussionID', $DiscussionID)->delete('TagDiscussion');
        }
    }

    /**
     * Add the tag input to the discussion form.
     *
     * @param Gdn_Controller $Sender
     */
    public function postController_afterDiscussionFormOptions_handler($Sender) {
        if (!c('EnabledPlugins.Tagging')) {
            return;
        }

        if (in_array($Sender->RequestMethod, array('discussion', 'editdiscussion', 'question'))) {
            // Setup, get most popular tags
            $TagModel = TagModel::instance();
            $Tags = $TagModel->getWhere(array('Type' => array_keys($TagModel->defaultTypes())), 'CountDiscussions', 'desc', c('Plugins.Tagging.ShowLimit', 50))->Result(DATASET_TYPE_ARRAY);
            $TagsHtml = (count($Tags)) ? '' : t('No tags have been created yet.');
            $Tags = Gdn_DataSet::index($Tags, 'FullName');
            ksort($Tags);

            // The tags must be fetched.
            if ($Sender->Request->isPostBack()) {
                $tag_ids = TagModel::SplitTags($Sender->Form->getFormValue('Tags'));
                $tags = TagModel::instance()->getWhere(array('TagID' => $tag_ids))->resultArray();
                $tags = array_column($tags, 'TagID', 'FullName');
            } else {
                // The tags should be set on the data.
                $tags = array_column($Sender->data('Tags', array()), 'FullName', 'TagID');
                $xtags = $Sender->data('XTags', array());
                foreach (TagModel::instance()->defaultTypes() as $key => $row) {
                    if (isset($xtags[$key])) {
                        $xtags2 = array_column($xtags[$key], 'FullName', 'TagID');
                        foreach ($xtags2 as $id => $name) {
                            $tags[$id] = $name;
                        }
                    }
                }
            }

            echo '<div class="Form-Tags P">';

            // Tag text box
            echo $Sender->Form->label('Tags', 'Tags');
            echo $Sender->Form->textBox('Tags', array('data-tags' => json_encode($tags)));

            // Available tags
            echo wrap(Anchor(t('Show popular tags'), '#'), 'span', array('class' => 'ShowTags'));
            foreach ($Tags as $Tag) {
                $TagsHtml .= anchor(htmlspecialchars($Tag['FullName']), '#', 'AvailableTag', array('data-name' => $Tag['Name'], 'data-id' => $Tag['TagID'])).' ';
            }
            echo wrap($TagsHtml, 'div', array('class' => 'Hidden AvailableTags'));

            echo '</div>';
        }
    }

    /**
     * Add javascript to the post/edit discussion page so that tagging autocomplete works.
     *
     * @param PostController $Sender
     */
    public function postController_render_before($Sender) {
        $Sender->addDefinition('PluginsTaggingAdd', Gdn::session()->checkPermission('Plugins.Tagging.Add'));
        $Sender->addDefinition('PluginsTaggingSearchUrl', Gdn::request()->Url('plugin/tagsearch'));
        $Sender->addDefinition('MaxTagsAllowed', c('Plugin.Tagging.Max', 5));

        // Make sure that detailed tag data is available to the form.
        $TagModel = TagModel::instance();

        $DiscussionID = $Sender->data('Discussion.DiscussionID');

        if ($DiscussionID) {
            $Tags = $TagModel->getDiscussionTags($DiscussionID, TagModel::IX_EXTENDED);
            $Sender->setData($Tags);
        } elseif (!$Sender->Request->isPostBack() && $tagString = $Sender->Request->get('tags')) {
            $tags = explodeTrim(',', $tagString);
            $types = array_column(TagModel::instance()->defaultTypes(), 'key');

            // Look up the tags by name.
            $tagData = Gdn::sql()->getWhere(
                'Tag',
                array('Name' => $tags, 'Type' => $types)
            )->resultArray();

            // Add any missing tags.
            $tagNames = array_change_key_case(array_column($tagData, 'Name', 'Name'));
            foreach ($tags as $tag) {
                $tagKey = strtolower($tag);
                if (!isset($tagNames[$tagKey])) {
                    $tagData[] = array('TagID' => $tag, 'Name' => $tagKey, 'FullName' => $tag, 'Type' => '');
                }
            }

            $Sender->setData('Tags', $tagData);
        }
    }

    /**
     * Provide default permissions for roles, based on the value in their Type column.
     *
     * @param PermissionModel $Sender Instance of permission model that fired the event
     */
    public function permissionModel_defaultPermissions_handler($Sender) {
        // Guest defaults
        $Sender->addDefault(
            RoleModel::TYPE_GUEST,
            array(
                'Vanilla.Discussions.View' => 1
            )
        );
        $Sender->addDefault(
            RoleModel::TYPE_GUEST,
            array(
                'Vanilla.Discussions.View' => 1
            ),
            'Category',
            -1
        );

        // Unconfirmed defaults
        $Sender->addDefault(
            RoleModel::TYPE_UNCONFIRMED,
            array(
                'Vanilla.Discussions.View' => 1
            )
        );
        $Sender->addDefault(
            RoleModel::TYPE_UNCONFIRMED,
            array(
                'Vanilla.Discussions.View' => 1
            ),
            'Category',
            -1
        );

        // Applicant defaults
        $Sender->addDefault(
            RoleModel::TYPE_APPLICANT,
            array(
                'Vanilla.Discussions.View' => 1
            )
        );
        $Sender->addDefault(
            RoleModel::TYPE_APPLICANT,
            array(
                'Vanilla.Discussions.View' => 1
            ),
            'Category',
            -1
        );

        // Member defaults
        $Sender->addDefault(
            RoleModel::TYPE_MEMBER,
            array(
                'Vanilla.Discussions.Add' => 1,
                'Vanilla.Discussions.View' => 1,
                'Vanilla.Comments.Add' => 1
            )
        );
        $Sender->addDefault(
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
        $Sender->addDefault(
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
        $Sender->addDefault(
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
        $Sender->addDefault(
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
        $Sender->addDefault(
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
    public function userModel_beforeDeleteUser_handler($Sender) {
        $UserID = val('UserID', $Sender->EventArguments);
        $Options = val('Options', $Sender->EventArguments, array());
        $Options = is_array($Options) ? $Options : array();
        $Content = &$Sender->EventArguments['Content'];

        $this->deleteUserData($UserID, $Options, $Content);
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
    public function userModel_getCategoryViewPermission_create($Sender) {
        static $PermissionModel = null;

        $UserID = val(0, $Sender->EventArguments, '');
        $CategoryID = val(1, $Sender->EventArguments, '');
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

            $Result = $PermissionModel->getUserPermissions($UserID, $Permission, 'Category', 'PermissionCategoryID', 'CategoryID', $PermissionCategoryID);
            return (val($Permission, val(0, $Result), false)) ? true : false;
        }
        return false;
    }


    /**
     * Add CSS assets to front end.
     *
     * @param AssetModel $sender
     */
    public function assetModel_afterGetCssFiles_handler($sender) {
        if (!inSection('Dashboard')) {
            $sender->addCssFile('tag.css', 'vanilla', ['Sort' => 800]);
        }
    }

    /**
     * Adds 'Discussion' item to menu.
     *
     * 'base_render_before' will trigger before every pageload across apps.
     * If you abuse this hook, Tim will throw a Coke can at your head.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param Gdn_Controller $sender The sending controller object.
     */
    public function base_render_before($sender) {
        if ($sender->Menu) {
            $sender->Menu->addLink('Discussions', t('Discussions'), '/discussions', false, ['Standard' => true]);
        }

        if (!inSection('Dashboard')) {
            // Spoilers assets
            $sender->addJsFile('spoilers.js', 'vanilla');
            $sender->addCssFile('spoilers.css', 'vanilla');
            $sender->addDefinition('Spoiler', t('Spoiler'));
            $sender->addDefinition('show', t('show'));
            $sender->addDefinition('hide', t('hide'));
        }

        // Add user's viewable roles to gdn.meta if user is logged in.
        if (!$sender->addDefinition('Roles')) {
            if (Gdn::session()->isValid()) {
                $roleModel = new RoleModel();
                Gdn::controller()->addDefinition("Roles", $roleModel->getPublicUserRoles(Gdn::session()->UserID, "Name"));
            }
        }

        // Tagging BEGIN
        // Set breadcrumbs where relevant
        if (null !== $sender->data('Tag', null) && null !== $sender->data('Tags')) {
            $ParentTag = array();
            $CurrentTag = $sender->data('Tag');
            $CurrentTags = $sender->data('Tags');

            $ParentTagID = ($CurrentTag['ParentTagID'])
                ? $CurrentTag['ParentTagID']
                : '';

            foreach ($CurrentTags as $Tag) {
                foreach ($Tag as $SubTag) {
                    if ($SubTag['TagID'] == $ParentTagID) {
                        $ParentTag = $SubTag;
                    }
                }
            }

            $Breadcrumbs = array();

            if (is_array($ParentTag) && count(array_filter($ParentTag))) {
                $Breadcrumbs[] = array('Name' => $ParentTag['FullName'], 'Url' => TagUrl($ParentTag, '', '/'));
            }

            if (is_array($CurrentTag) && count(array_filter($CurrentTag))) {
                $Breadcrumbs[] = array('Name' => $CurrentTag['FullName'], 'Url' => TagUrl($CurrentTag, '', '/'));
            }

            if (count($Breadcrumbs)) {
                // Rebuild breadcrumbs in discussions when there is a child, as the
                // parent must come before it.
                $sender->setData('Breadcrumbs', $Breadcrumbs);
            }
        }

        if (null !== $sender->data('Announcements', null)) {
            TagModel::instance()->joinTags($sender->Data['Announcements']);
        }

        if (null !== $sender->data('Discussions', null)) {
            TagModel::instance()->joinTags($sender->Data['Discussions']);
        }

        $sender->addJsFile('tagging.js', 'vanilla');
        $sender->addJsFile('jquery.tokeninput.js');
        // Tagging END
    }

    /**
     * Adds 'Discussions' tab to profiles and adds CSS & JS files to their head.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param object $Sender ProfileController.
     */
    public function profileController_addProfileTabs_handler($Sender) {
        if (is_object($Sender->User) && $Sender->User->UserID > 0) {
            $UserID = $Sender->User->UserID;
            // Add the discussion tab
            $DiscussionsLabel = sprite('SpDiscussions').' '.t('Discussions');
            $CommentsLabel = sprite('SpComments').' '.t('Comments');
            if (c('Vanilla.Profile.ShowCounts', true)) {
                $DiscussionsLabel .= '<span class="Aside">'.CountString(GetValueR('User.CountDiscussions', $Sender, null), "/profile/count/discussions?userid=$UserID").'</span>';
                $CommentsLabel .= '<span class="Aside">'.CountString(GetValueR('User.CountComments', $Sender, null), "/profile/count/comments?userid=$UserID").'</span>';
            }
            $Sender->addProfileTab(t('Discussions'), 'profile/discussions/'.$Sender->User->UserID.'/'.rawurlencode($Sender->User->Name), 'Discussions', $DiscussionsLabel);
            $Sender->addProfileTab(t('Comments'), 'profile/comments/'.$Sender->User->UserID.'/'.rawurlencode($Sender->User->Name), 'Comments', $CommentsLabel);
            // Add the discussion tab's CSS and Javascript.
            $Sender->addJsFile('jquery.gardenmorepager.js');
            $Sender->addJsFile('discussions.js', 'vanilla');
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
    public function profileController_afterPreferencesDefined_handler($Sender) {
        $Sender->Preferences['Notifications']['Email.DiscussionComment'] = t('Notify me when people comment on my discussions.');
        $Sender->Preferences['Notifications']['Email.BookmarkComment'] = t('Notify me when people comment on my bookmarked discussions.');
        $Sender->Preferences['Notifications']['Email.Mention'] = t('Notify me when people mention me.');
        $Sender->Preferences['Notifications']['Email.ParticipateComment'] = t('Notify me when people comment on discussions I\'ve participated in.');

        $Sender->Preferences['Notifications']['Popup.DiscussionComment'] = t('Notify me when people comment on my discussions.');
        $Sender->Preferences['Notifications']['Popup.BookmarkComment'] = t('Notify me when people comment on my bookmarked discussions.');
        $Sender->Preferences['Notifications']['Popup.Mention'] = t('Notify me when people mention me.');
        $Sender->Preferences['Notifications']['Popup.ParticipateComment'] = t('Notify me when people comment on discussions I\'ve participated in.');

        if (Gdn::session()->checkPermission('Garden.AdvancedNotifications.Allow')) {
            $PostBack = $Sender->Form->authenticatedPostBack();
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
                UserModel::setMeta($Sender->User->UserID, $Set, 'Preferences.');
            }
        }
    }

    /**
     * Add the advanced notifications view to profiles.
     *
     * @param ProfileController $Sender
     */
    public function profileController_CustomNotificationPreferences_Handler($Sender) {
        if (Gdn::session()->checkPermission('Garden.AdvancedNotifications.Allow')) {
            include $Sender->fetchViewLocation('notificationpreferences', 'vanillasettings', 'vanilla');
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
        $SearchModel->search($Sender);
    }

    /**
     * @param NavModule $sender
     */
    public function siteNavModule_init_handler($sender) {
        // Grab the default route so that we don't add a link to it twice.
        $home = trim(val('Destination', Gdn::router()->getRoute('DefaultController')), '/');

        // Add the site discussion links.
        $sender->addLinkIf($home !== 'categories', t('All Categories', 'Categories'), '/categories', 'main.categories', '', 1, array('icon' => 'th-list'));
        $sender->addLinkIf($home !== 'discussions', t('Recent Discussions'), '/discussions', 'main.discussions', '', 1, array('icon' => 'discussion'));
        $sender->addGroup(t('Favorites'), 'favorites', '', 3);

        if (Gdn::session()->isValid()) {
            $sender->addLink(t('My Bookmarks'), '/discussions/bookmarked', 'favorites.bookmarks', '', array(), array('icon' => 'star', 'badge' => Gdn::session()->User->CountBookmarks));
            $sender->addLink(t('My Discussions'), '/discussions/mine', 'favorites.discussions', '', array(), array('icon' => 'discussion', 'badge' => Gdn::session()->User->CountDiscussions));
            $sender->addLink(t('Drafts'), '/drafts', 'favorites.drafts', '', array(), array('icon' => 'compose', 'badge' => Gdn::session()->User->CountDrafts));
        }

        $user = Gdn::controller()->data('Profile');
        if (!$user) {
            return;
        }
        $sender->addGroupToSection('Profile', t('Posts'), 'posts');
        $sender->addLinkToSection('Profile', t('Discussions'), userUrl($user, '', 'discussions'), 'posts.discussions', '', array(), array('icon' => 'discussion', 'badge' => val('CountDiscussions', $user)));
        $sender->addLinkToSection('Profile', t('Comments'), userUrl($user, '', 'comments'), 'posts.comments', '', array(), array('icon' => 'comment', 'badge' => val('CountComments', $user)));
    }

    /**
     * Creates virtual 'Comments' method in ProfileController.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param ProfileController $Sender ProfileController.
     */
    public function profileController_comments_create($Sender, $UserReference = '', $Username = '', $Page = '', $UserID = '') {
        $Sender->editMode(false);
        $View = $Sender->View;

        // Tell the ProfileController what tab to load
        $Sender->getUserInfo($UserReference, $Username, $UserID);
        $Sender->_setBreadcrumbs(t('Comments'), userUrl($Sender->User, '', 'comments'));
        $Sender->SetTabView('Comments', 'profile', 'Discussion', 'Vanilla');

        $PageSize = c('Vanilla.Discussions.PerPage', 30);
        list($Offset, $Limit) = offsetLimit($Page, $PageSize);

        $CommentModel = new CommentModel();
        $Comments = $CommentModel->getByUser2($Sender->User->UserID, $Limit, $Offset, $Sender->Request->get('lid'));
        $TotalRecords = $Offset + $CommentModel->LastCommentCount + 1;

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $Sender->Pager = $PagerFactory->getPager('MorePager', $Sender);
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
    public function profileController_discussions_create($Sender, $UserReference = '', $Username = '', $Page = '', $UserID = '') {
        $Sender->editMode(false);

        // Tell the ProfileController what tab to load
        $Sender->getUserInfo($UserReference, $Username, $UserID);
        $Sender->_setBreadcrumbs(t('Discussions'), userUrl($Sender->User, '', 'discussions'));
        $Sender->setTabView('Discussions', 'Profile', 'Discussions', 'Vanilla');
        $Sender->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);

        list($Offset, $Limit) = offsetLimit($Page, c('Vanilla.Discussions.PerPage', 30));

        $DiscussionModel = new DiscussionModel();
        $Discussions = $DiscussionModel->getByUser($Sender->User->UserID, $Limit, $Offset, false, Gdn::session()->UserID);
        $CountDiscussions = $Offset + $DiscussionModel->LastDiscussionCount + 1;
        $Sender->DiscussionData = $Sender->setData('Discussions', $Discussions);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $Sender->Pager = $PagerFactory->getPager('MorePager', $Sender);
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
    public function settingsController_defineAdminPermissions_handler($Sender) {
        if (isset($Sender->RequiredAdminPermissions)) {
            $Sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
        }
    }

    /**
     * Discussion view counter.
     *
     * @param $Sender
     * @param $Args
     */
    public function gdn_statistics_tick_handler($Sender, $Args) {
        $Path = Gdn::request()->post('Path');
        $Args = Gdn::request()->post('Args');
        parse_str($Args, $Args);
        $ResolvedPath = trim(Gdn::request()->post('ResolvedPath'), '/');
        $ResolvedArgs = Gdn::request()->post('ResolvedArgs');
        $DiscussionID = null;
        $DiscussionModel = new DiscussionModel();

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
                    $Discussion = $DiscussionModel->getForeignID($ForeignID, 'page');
                    $DiscussionID = val('DiscussionID', $Discussion);
                    Gdn::cache()->store($Key, $DiscussionID, array(Gdn_Cache::FEATURE_EXPIRY, 1800));
                }
            }
        }

        if ($DiscussionID) {
            $DiscussionModel->addView($DiscussionID);
        }
    }

    /**
     * Adds items to Dashboard menu.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param DashboardNavModule $sender
     */
    public function dashboardNavModule_init_handler($sender) {
        $sort = -1; // Ensure these items go before any plugin items.

        $sender->addLinkIf('Garden.Community.Manage', t('Categories'), '/vanilla/settings/categories', 'forum.manage-categories', 'nav-manage-categories', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Advanced'), '/vanilla/settings/advanced', 'forum.advanced', 'nav-forum-advanced', $sort)
            ->addLinkIf(c('Vanilla.Archive.Date', false) &&  Gdn::session()->checkPermission('Garden.Settings.Manage'), t('Archive Discussions'), '/vanilla/settings/archive', 'forum.archive', 'nav-forum-archive', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Embed'), 'embed/forum', 'forum.embed-site', 'nav-embed nav-embed-site', $sort)
            ->addLinkToSectionIf('Garden.Settings.Manage', 'Moderation', t('Flood Control'), '/vanilla/settings/floodcontrol', 'moderation.flood-control', 'nav-flood-control', $sort);
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
        $Drop = false;

        // Call structure.php to update database
        $Validation = new Gdn_Validation(); // Needed by structure.php to validate permission names
        include(PATH_APPLICATIONS.DS.'vanilla'.DS.'settings'.DS.'structure.php');

        saveToConfig('Routes.DefaultController', 'discussions');
    }
}
