<?php
/**
 * VanillaHooks Plugin
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param DbaController $sender
     */
    public function dbaController_countJobs_handler($sender) {
        $counts = [
            'Discussion' => ['CountComments', 'FirstCommentID', 'LastCommentID', 'DateLastComment', 'LastCommentUserID'],
            'Category' => ['CountDiscussions', 'CountAllDiscussions', 'CountComments', 'CountAllComments', 'LastDiscussionID', 'LastCommentID', 'LastDateInserted'],
            'Tag' => ['CountDiscussions'],
        ];

        foreach ($counts as $table => $columns) {
            foreach ($columns as $column) {
                $name = "Recalculate $table.$column";
                $url = "/dba/counts.json?".http_build_query(['table' => $table, 'column' => $column]);
                $sender->Data['Jobs'][$name] = $url;
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
    public function deleteUserData($userID, $options = [], &$data = null) {
        $sql = Gdn::sql();

        // Remove discussion watch records and drafts.
        $sql->delete('UserDiscussion', ['UserID' => $userID]);

        Gdn::userModel()->getDelete('Draft', ['InsertUserID' => $userID], $data);

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

            Gdn::userModel()->getDelete('Comment', ['InsertUserID' => $userID], $data);

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
                    ['CountComments' => $row['CountComments'] + 1, 'LastCommentID' => $row['LastCommentID']],
                    ['DiscussionID' => $row['DiscussionID']]
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
            Gdn::userModel()->getDelete('Discussion', ['InsertUserID' => $userID], $data);

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
            ->set([
                'CountDiscussions' => 0,
                'CountUnreadDiscussions' => 0,
                'CountComments' => 0,
                'CountDrafts' => 0,
                'CountBookmarks' => 0
            ])
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
     * @param DiscussionController $sender
     */
    public function discussionController_afterDiscussionBody_handler($sender) {
        /*  */
        // Allow disabling of inline tags.
        if (!c('Vanilla.Tagging.DisableInline', false)) {
            if (!property_exists($sender->EventArguments['Object'], 'CommentID')) {
                $discussionID = property_exists($sender, 'DiscussionID') ? $sender->DiscussionID : 0;

                if (!$discussionID) {
                    return;
                }

                $tagModule = new TagModule($sender);
                echo $tagModule->inlineDisplay();
            }
        }
    }

    /**
     * Validate tags when saving a discussion.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_beforeSaveDiscussion_handler($sender, $args) {
        // Allow an addon to set disallowed tag names.
        $reservedTags = [];
        $sender->EventArguments['ReservedTags'] = &$reservedTags;
        $sender->fireEvent('ReservedTags');

        // Set some tagging requirements.
        $tagsString = trim(strtolower(valr('FormPostValues.Tags', $args, '')));
        if (stringIsNullOrEmpty($tagsString) && c('Vanilla.Tagging.Required')) {
            $sender->Validation->addValidationResult('Tags', 'You must specify at least one tag.');
        } else {
            // Break apart our tags and lowercase them all for comparisons.
            $tags = TagModel::splitTags($tagsString);
            $tags = array_map('strtolower', $tags);
            $reservedTags = array_map('strtolower', $reservedTags);
            $maxTags = c('Vanilla.Tagging.Max', 5);

            // Validate our tags.
            if ($reservedTags = array_intersect($tags, $reservedTags)) {
                $names = implode(', ', $reservedTags);
                $sender->Validation->addValidationResult('Tags', '@'.sprintf(t('These tags are reserved and cannot be used: %s'), $names));
            }
            if (!TagModel::validateTags($tags)) {
                $sender->Validation->addValidationResult('Tags', '@'.t('ValidateTag', 'Tags cannot contain commas.'));
            }
            if (count($tags) > $maxTags) {
                $sender->Validation->addValidationResult('Tags', '@'.sprintf(t('You can only specify up to %s tags.'), $maxTags));
            }
        }
    }

    /**
     * Save tags when saving a discussion.
     *
     * @param DiscussionModel $sender
     */
    public function discussionModel_afterSaveDiscussion_handler($sender) {
        $formPostValues = val('FormPostValues', $sender->EventArguments, []);
        $discussionID = val('DiscussionID', $sender->EventArguments, 0);
        $categoryID = valr('Fields.CategoryID', $sender->EventArguments, 0);
        $rawFormTags = val('Tags', $formPostValues, '');
        $formTags = TagModel::splitTags($rawFormTags);

        // If we're associating with categories
        $categorySearch = c('Vanilla.Tagging.CategorySearch', false);
        if ($categorySearch) {
            $categoryID = val('CategoryID', $formPostValues, false);
        }

        // Let plugins have their information getting saved.
        $types = [''];

        // We fire as TaggingPlugin since this code was taken from the old TaggingPlugin and we do not
        // want to break any hooks
        Gdn::pluginManager()->fireAs('TaggingPlugin')->fireEvent('SaveDiscussion', [
            'Data' => $formPostValues,
            'Tags' => &$formTags,
            'Types' => &$types,
            'CategoryID' => $categoryID,
        ]);

        // Save the tags to the db.
        TagModel::instance()->saveDiscussion($discussionID, $formTags, $types, $categoryID);
    }

    /**
     * Handle tag association deletion when a discussion is deleted.
     *
     * @param $sender
     * @throws Exception
     */
    public function discussionModel_deleteDiscussion_handler($sender) {
        // Get discussionID that is being deleted
        $discussionID = $sender->EventArguments['DiscussionID'];

        // Get List of tags to reduce count for
        $tagDataSet = Gdn::sql()->select('TagID')
            ->from('TagDiscussion')
            ->where('DiscussionID', $discussionID)
            ->get()->resultArray();

        $removedTagIDs = array_column($tagDataSet, 'TagID');

        // Check if there are even any tags to delete
        if (count($removedTagIDs) > 0) {
            // Step 1: Reduce count
            Gdn::sql()
                ->update('Tag')
                ->set('CountDiscussions', 'CountDiscussions - 1', false)
                ->whereIn('TagID', $removedTagIDs)
                ->put();

            // Step 2: Delete mapping data between discussion and tag (tagdiscussion table)
            $sender->SQL->where('DiscussionID', $discussionID)->delete('TagDiscussion');
        }
    }

    /**
     * Add the tag input to the discussion form.
     *
     * @param Gdn_Controller $Sender
     */
    public function postController_afterDiscussionFormOptions_handler($Sender) {
        if (!c('Tagging.Discussions.Enabled')) {
            return;
        }

        if (in_array($Sender->RequestMethod, ['discussion', 'editdiscussion', 'question'])) {
            // Setup, get most popular tags
            $TagModel = TagModel::instance();
            $Tags = $TagModel->getWhere(['Type' => array_keys($TagModel->defaultTypes())], 'CountDiscussions', 'desc', c('Vanilla.Tagging.ShowLimit', 50))->result(DATASET_TYPE_ARRAY);
            $TagsHtml = (count($Tags)) ? '' : t('No tags have been created yet.');
            $Tags = Gdn_DataSet::index($Tags, 'FullName');
            ksort($Tags);

            // The tags must be fetched.
            if ($Sender->Request->isPostBack()) {
                $tag_ids = TagModel::splitTags($Sender->Form->getFormValue('Tags'));
                $tags = TagModel::instance()->getWhere(['TagID' => $tag_ids])->resultArray();
                $tags = array_column($tags, 'TagID', 'FullName');
            } else {
                // The tags should be set on the data.
                $tags = array_column($Sender->data('Tags', []), 'FullName', 'TagID');
                $xtags = $Sender->data('XTags', []);
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
            echo $Sender->Form->textBox('Tags', ['data-tags' => json_encode($tags)]);

            // Available tags
            echo wrap(anchor(t('Show popular tags'), '#'), 'span', ['class' => 'ShowTags']);
            foreach ($Tags as $Tag) {
                $TagsHtml .= anchor(htmlspecialchars($Tag['FullName']), '#', 'AvailableTag', ['data-name' => $Tag['Name'], 'data-id' => $Tag['TagID']]).' ';
            }
            echo wrap($TagsHtml, 'div', ['class' => 'Hidden AvailableTags']);

            echo '</div>';
        }
    }

    /**
     * Add javascript to the post/edit discussion page so that tagging autocomplete works.
     *
     * @param PostController $Sender
     */
    public function postController_render_before($Sender) {
        $Sender->addDefinition('TaggingAdd', Gdn::session()->checkPermission('Vanilla.Tagging.Add'));
        $Sender->addDefinition('TaggingSearchUrl', Gdn::request()->url('tags/search'));
        $Sender->addDefinition('MaxTagsAllowed', c('Vanilla.Tagging.Max', 5));

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
                ['Name' => $tags, 'Type' => $types]
            )->resultArray();

            // Add any missing tags.
            $tagNames = array_change_key_case(array_column($tagData, 'Name', 'Name'));
            foreach ($tags as $tag) {
                $tagKey = strtolower($tag);
                if (!isset($tagNames[$tagKey])) {
                    $tagData[] = ['TagID' => $tag, 'Name' => $tagKey, 'FullName' => $tag, 'Type' => ''];
                }
            }

            $Sender->setData('Tags', $tagData);
        }
    }

    /**
     * Provide default permissions for roles, based on the value in their Type column.
     *
     * @param PermissionModel $sender Instance of permission model that fired the event
     */
    public function permissionModel_defaultPermissions_handler($sender) {
        // Guest defaults
        $sender->addDefault(
            RoleModel::TYPE_GUEST,
            [
                'Vanilla.Discussions.View' => 1
            ]
        );
        $sender->addDefault(
            RoleModel::TYPE_GUEST,
            [
                'Vanilla.Discussions.View' => 1
            ],
            'Category',
            -1
        );

        // Unconfirmed defaults
        $sender->addDefault(
            RoleModel::TYPE_UNCONFIRMED,
            [
                'Vanilla.Discussions.View' => 1
            ]
        );
        $sender->addDefault(
            RoleModel::TYPE_UNCONFIRMED,
            [
                'Vanilla.Discussions.View' => 1
            ],
            'Category',
            -1
        );

        // Applicant defaults
        $sender->addDefault(
            RoleModel::TYPE_APPLICANT,
            [
                'Vanilla.Discussions.View' => 1
            ]
        );
        $sender->addDefault(
            RoleModel::TYPE_APPLICANT,
            [
                'Vanilla.Discussions.View' => 1
            ],
            'Category',
            -1
        );

        // Member defaults
        $sender->addDefault(
            RoleModel::TYPE_MEMBER,
            [
                'Vanilla.Discussions.Add' => 1,
                'Vanilla.Discussions.View' => 1,
                'Vanilla.Comments.Add' => 1
            ]
        );
        $sender->addDefault(
            RoleModel::TYPE_MEMBER,
            [
                'Vanilla.Discussions.Add' => 1,
                'Vanilla.Discussions.View' => 1,
                'Vanilla.Comments.Add' => 1
            ],
            'Category',
            -1
        );

        // Moderator defaults
        $sender->addDefault(
            RoleModel::TYPE_MODERATOR,
            [
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
            ]
        );
        $sender->addDefault(
            RoleModel::TYPE_MODERATOR,
            [
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
            ],
            'Category',
            -1
        );

        // Administrator defaults
        $sender->addDefault(
            RoleModel::TYPE_ADMINISTRATOR,
            [
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
            ]
        );
        $sender->addDefault(
            RoleModel::TYPE_ADMINISTRATOR,
            [
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
            ],
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
     * @param UserModel $sender UserModel.
     */
    public function userModel_beforeDeleteUser_handler($sender) {
        $userID = val('UserID', $sender->EventArguments);
        $options = val('Options', $sender->EventArguments, []);
        $options = is_array($options) ? $options : [];
        $content = &$sender->EventArguments['Content'];

        $this->deleteUserData($userID, $options, $content);
    }

    /**
     * Check whether a user has access to view discussions in a particular category.
     *
     * @since 2.0.18
     * @example $UserModel->getCategoryViewPermission($userID, $categoryID).
     *
     * @param $sender UserModel.
     * @return bool Whether user has permission.
     */
    public function userModel_getCategoryViewPermission_create($sender) {
        $userID = val(0, $sender->EventArguments, '');
        $categoryID = val(1, $sender->EventArguments, '');
        $permission = val(2, $sender->EventArguments, 'Vanilla.Discussions.View');
        if ($userID && $categoryID) {
            $category = CategoryModel::categories($categoryID);
            if ($category) {
                $permissionCategoryID = $category['PermissionCategoryID'];
            } else {
                $permissionCategoryID = -1;
            }

            $options = ['ForeignID' => $permissionCategoryID];
            $result = Gdn::userModel()->checkPermission($userID, $permission, $options);
            return $result;
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
            $parentTag = [];
            $currentTag = $sender->data('Tag');
            $currentTags = $sender->data('Tags');

            $parentTagID = ($currentTag['ParentTagID'])
                ? $currentTag['ParentTagID']
                : '';

            foreach ($currentTags as $tag) {
                foreach ($tag as $subTag) {
                    if ($subTag['TagID'] == $parentTagID) {
                        $parentTag = $subTag;
                    }
                }
            }

            $breadcrumbs = [];

            if (is_array($parentTag) && count(array_filter($parentTag))) {
                $breadcrumbs[] = ['Name' => $parentTag['FullName'], 'Url' => tagUrl($parentTag, '', '/')];
            }

            if (is_array($currentTag) && count(array_filter($currentTag))) {
                $breadcrumbs[] = ['Name' => $currentTag['FullName'], 'Url' => tagUrl($currentTag, '', '/')];
            }

            if (count($breadcrumbs)) {
                // Rebuild breadcrumbs in discussions when there is a child, as the
                // parent must come before it.
                $sender->setData('Breadcrumbs', $breadcrumbs);
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
     * @param object $sender ProfileController.
     */
    public function profileController_addProfileTabs_handler($sender) {
        if (is_object($sender->User) && $sender->User->UserID > 0) {
            $userID = $sender->User->UserID;
            // Add the discussion tab
            $discussionsLabel = sprite('SpDiscussions').' '.t('Discussions');
            $commentsLabel = sprite('SpComments').' '.t('Comments');
            if (c('Vanilla.Profile.ShowCounts', true)) {
                $discussionsLabel .= '<span class="Aside">'.countString(getValueR('User.CountDiscussions', $sender, null), "/profile/count/discussions?userid=$userID").'</span>';
                $commentsLabel .= '<span class="Aside">'.countString(getValueR('User.CountComments', $sender, null), "/profile/count/comments?userid=$userID").'</span>';
            }
            $sender->addProfileTab(t('Discussions'), 'profile/discussions/'.$sender->User->UserID.'/'.rawurlencode($sender->User->Name), 'Discussions', $discussionsLabel);
            $sender->addProfileTab(t('Comments'), 'profile/comments/'.$sender->User->UserID.'/'.rawurlencode($sender->User->Name), 'Comments', $commentsLabel);
            // Add the discussion tab's CSS and Javascript.
            $sender->addJsFile('jquery.gardenmorepager.js');
            $sender->addJsFile('discussions.js', 'vanilla');
        }
    }

    /**
     * Adds email notification options to profiles.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param ProfileController $sender
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.DiscussionComment'] = t('Notify me when people comment on my discussions.');
        $sender->Preferences['Notifications']['Email.BookmarkComment'] = t('Notify me when people comment on my bookmarked discussions.');
        $sender->Preferences['Notifications']['Email.Mention'] = t('Notify me when people mention me.');
        $sender->Preferences['Notifications']['Email.ParticipateComment'] = t('Notify me when people comment on discussions I\'ve participated in.');

        $sender->Preferences['Notifications']['Popup.DiscussionComment'] = t('Notify me when people comment on my discussions.');
        $sender->Preferences['Notifications']['Popup.BookmarkComment'] = t('Notify me when people comment on my bookmarked discussions.');
        $sender->Preferences['Notifications']['Popup.Mention'] = t('Notify me when people mention me.');
        $sender->Preferences['Notifications']['Popup.ParticipateComment'] = t('Notify me when people comment on discussions I\'ve participated in.');

        if (Gdn::session()->checkPermission('Garden.AdvancedNotifications.Allow')) {
            $postBack = $sender->Form->authenticatedPostBack();
            $set = [];

            // Add the category definitions to for the view to pick up.
            $doHeadings = c('Vanilla.Categories.DoHeadings');
            // Grab all of the categories.
            $categories = [];
            $prefixes = ['Email.NewDiscussion', 'Popup.NewDiscussion', 'Email.NewComment', 'Popup.NewComment'];
            foreach (CategoryModel::categories() as $category) {
                if (!$category['PermsDiscussionsView'] || $category['Depth'] <= 0 || $category['Depth'] > 2 || $category['Archived']) {
                    continue;
                }

                $category['Heading'] = ($doHeadings && $category['Depth'] <= 1);
                $categories[] = $category;

                if ($postBack) {
                    foreach ($prefixes as $prefix) {
                        $fieldName = "$prefix.{$category['CategoryID']}";
                        $value = $sender->Form->getFormValue($fieldName, null);
                        if (!$value) {
                            $value = null;
                        }
                        $set[$fieldName] = $value;
                    }
                }
            }
            $sender->setData('CategoryNotifications', $categories);
            if ($postBack) {
                UserModel::setMeta($sender->User->UserID, $set, 'Preferences.');
            }
        }
    }

    /**
     * Add the advanced notifications view to profiles.
     *
     * @param ProfileController $Sender
     */
    public function profileController_customNotificationPreferences_handler($Sender) {
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
     * @param object $sender SearchModel
     */
    public function searchModel_search_handler($sender) {
        $searchModel = new VanillaSearchModel();
        $searchModel->search($sender);
    }

    /**
     * @param NavModule $sender
     */
    public function siteNavModule_init_handler($sender) {
        // Grab the default route so that we don't add a link to it twice.
        $home = trim(val('Destination', Gdn::router()->getRoute('DefaultController')), '/');

        // Add the site discussion links.
        $sender->addLinkIf($home !== 'categories', t('All Categories', 'Categories'), '/categories', 'main.categories', '', 1, ['icon' => 'th-list']);
        $sender->addLinkIf($home !== 'discussions', t('Recent Discussions'), '/discussions', 'main.discussions', '', 1, ['icon' => 'discussion']);
        $sender->addGroup(t('Favorites'), 'favorites', '', 3);

        if (Gdn::session()->isValid()) {
            $sender->addLink(t('My Bookmarks'), '/discussions/bookmarked', 'favorites.bookmarks', '', [], ['icon' => 'star', 'badge' => Gdn::session()->User->CountBookmarks]);
            $sender->addLink(t('My Discussions'), '/discussions/mine', 'favorites.discussions', '', [], ['icon' => 'discussion', 'badge' => Gdn::session()->User->CountDiscussions]);
            $sender->addLink(t('Drafts'), '/drafts', 'favorites.drafts', '', [], ['icon' => 'compose', 'badge' => Gdn::session()->User->CountDrafts]);
        }

        $user = Gdn::controller()->data('Profile');
        if (!$user) {
            return;
        }
        $sender->addGroupToSection('Profile', t('Posts'), 'posts');
        $sender->addLinkToSection('Profile', t('Discussions'), userUrl($user, '', 'discussions'), 'posts.discussions', '', [], ['icon' => 'discussion', 'badge' => val('CountDiscussions', $user)]);
        $sender->addLinkToSection('Profile', t('Comments'), userUrl($user, '', 'comments'), 'posts.comments', '', [], ['icon' => 'comment', 'badge' => val('CountComments', $user)]);
    }

    /**
     * Creates virtual 'Comments' method in ProfileController.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param ProfileController $sender ProfileController.
     */
    public function profileController_comments_create($sender, $userReference = '', $username = '', $page = '', $userID = '') {
        $sender->permission('Garden.Profiles.View');

        $sender->editMode(false);
        $view = $sender->View;

        // Tell the ProfileController what tab to load
        $sender->getUserInfo($userReference, $username, $userID);
        $sender->_setBreadcrumbs(t('Comments'), userUrl($sender->User, '', 'comments'));
        $sender->setTabView('Comments', 'profile', 'Discussion', 'Vanilla');

        $pageSize = c('Vanilla.Discussions.PerPage', 30);
        list($offset, $limit) = offsetLimit($page, $pageSize);

        $commentModel = new CommentModel();
        $comments = $commentModel->getByUser2($sender->User->UserID, $limit, $offset, $sender->Request->get('lid'));
        $totalRecords = $offset + $commentModel->LastCommentCount + 1;

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $sender->Pager = $pagerFactory->getPager('MorePager', $sender);
        $sender->Pager->MoreCode = 'More Comments';
        $sender->Pager->LessCode = 'Newer Comments';
        $sender->Pager->ClientID = 'Pager';
        $sender->Pager->configure(
            $offset,
            $limit,
            $totalRecords,
            userUrl($sender->User, '', 'comments').'?page={Page}' //?lid='.$CommentModel->LastCommentID
        );

        // Deliver JSON data if necessary
        if ($sender->deliveryType() != DELIVERY_TYPE_ALL && $offset > 0) {
            $sender->setJson('LessRow', $sender->Pager->toString('less'));
            $sender->setJson('MoreRow', $sender->Pager->toString('more'));
            $sender->View = 'profilecomments';
        }
        $sender->setData('Comments', $comments);
        $sender->setData('UnfilteredCommentsCount', $commentModel->LastCommentCount);

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show discussion options
        $sender->ShowOptions = false;

        if ($sender->Head) {
            $sender->Head->addTag('meta', ['name' => 'robots', 'content' => 'noindex,noarchive']);
        }

        // Render the ProfileController
        $sender->render();
    }

    /**
     * Creates virtual 'Discussions' method in ProfileController.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param ProfileController $sender ProfileController.
     */
    public function profileController_discussions_create($sender, $userReference = '', $username = '', $page = '', $userID = '') {
        $sender->permission('Garden.Profiles.View');

        $sender->editMode(false);

        // Tell the ProfileController what tab to load
        $sender->getUserInfo($userReference, $username, $userID);
        $sender->_setBreadcrumbs(t('Discussions'), userUrl($sender->User, '', 'discussions'));
        $sender->setTabView('Discussions', 'Profile', 'Discussions', 'Vanilla');
        $sender->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);

        list($offset, $limit) = offsetLimit($page, c('Vanilla.Discussions.PerPage', 30));

        $discussionModel = new DiscussionModel();
        $discussions = $discussionModel->getByUser($sender->User->UserID, $limit, $offset, false, Gdn::session()->UserID);
        $countDiscussions = $offset + $discussionModel->LastDiscussionCount + 1;

        $sender->setData('UnfilteredDiscussionsCount', $discussionModel->LastDiscussionCount);
        $sender->DiscussionData = $sender->setData('Discussions', $discussions);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $sender->Pager = $pagerFactory->getPager('MorePager', $sender);
        $sender->Pager->MoreCode = 'More Discussions';
        $sender->Pager->LessCode = 'Newer Discussions';
        $sender->Pager->ClientID = 'Pager';
        $sender->Pager->configure(
            $offset,
            $limit,
            $countDiscussions,
            userUrl($sender->User, '', 'discussions').'?page={Page}'
        );

        // Deliver JSON data if necessary
        if ($sender->deliveryType() != DELIVERY_TYPE_ALL && $offset > 0) {
            $sender->setJson('LessRow', $sender->Pager->toString('less'));
            $sender->setJson('MoreRow', $sender->Pager->toString('more'));
            $sender->View = 'discussions';
        }

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show discussion options
        $sender->ShowOptions = false;

        if ($sender->Head) {
            // These pages offer only duplicate content to search engines and are a bit slow.
            $sender->Head->addTag('meta', ['name' => 'robots', 'content' => 'noindex,noarchive']);
        }

        // Render the ProfileController
        $sender->render();
    }

    /**
     * Makes sure forum administrators can see the dashboard admin pages.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param object $sender SettingsController.
     */
    public function settingsController_defineAdminPermissions_handler($sender) {
        if (isset($sender->RequiredAdminPermissions)) {
            $sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
        }
    }

    /**
     * Discussion view counter.
     *
     * @param $sender
     * @param $args
     */
    public function gdn_statistics_tick_handler($sender, $args) {
        $path = Gdn::request()->post('Path');
        $args = Gdn::request()->post('Args');
        parse_str($args, $args);
        $resolvedPath = trim(Gdn::request()->post('ResolvedPath'), '/');
        $resolvedArgs = Gdn::request()->post('ResolvedArgs');
        $discussionID = null;
        $discussionModel = new DiscussionModel();

        // Comment permalink
        if ($resolvedPath == 'vanilla/discussion/comment') {
            $commentID = val('CommentID', $resolvedArgs);
            $commentModel = new CommentModel();
            $comment = $commentModel->getID($commentID);
            $discussionID = val('DiscussionID', $comment);
        } // Discussion link
        elseif ($resolvedPath == 'vanilla/discussion/index') {
            $discussionID = val('DiscussionID', $resolvedArgs, null);
        } // Embedded discussion
        elseif ($resolvedPath == 'vanilla/discussion/embed') {
            $foreignID = val('vanilla_identifier', $args);
            if ($foreignID) {
                // This will be hit a lot so let's try caching it...
                $key = "DiscussionID.ForeignID.page.$foreignID";
                $discussionID = Gdn::cache()->get($key);
                if (!$discussionID) {
                    $discussion = $discussionModel->getForeignID($foreignID, 'page');
                    $discussionID = val('DiscussionID', $discussion);
                    Gdn::cache()->store($key, $discussionID, [Gdn_Cache::FEATURE_EXPIRY, 1800]);
                }
            }
        }

        if ($discussionID) {
            $discussionModel->addView($discussionID);
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
            ->addLinkIf('Garden.Settings.Manage', t('Posting'), '/vanilla/settings/posting', 'forum.posting', 'nav-forum-posting', $sort)
            ->addLinkIf(c('Vanilla.Archive.Date', false) &&  Gdn::session()->checkPermission('Garden.Settings.Manage'), t('Archive Discussions'), '/vanilla/settings/archive', 'forum.archive', 'nav-forum-archive', $sort)
            ->addLinkIf('Garden.Settings.Manage', t('Embedding'), 'embed/forum', 'site-settings.embed-site', 'nav-embed nav-embed-site', $sort)
            ->addLinkToSectionIf('Garden.Settings.Manage', 'Moderation', t('Flood Control'), '/vanilla/settings/floodcontrol', 'moderation.flood-control', 'nav-flood-control', $sort);
    }

    /**
     * Handle post-restore operations from the log table.
     *
     * @param LogModel $sender
     * @param array $args
     */
    public function logModel_afterRestore_handler($sender, $args) {
        $recordType = valr('Log.RecordType', $args);
        $recordUserID = valr('Log.RecordUserID', $args);

        if ($recordUserID === false) {
            return;
        }

        switch ($recordType) {
            case 'Comment':
                $commentModel = new CommentModel();
                $commentModel->updateUser($recordUserID, true);
                break;
            case 'Discussion':
                $discussionModel = new DiscussionModel();
                $discussionModel->updateUserDiscussionCount($recordUserID, true);
                break;
        }
    }

    /**
     * @deprecated Request /tags/search instead
     */
    public function pluginController_tagsearch_create() {
        $query = http_build_query(Gdn::request()->getQuery());
        redirectTo(url('/tags/search'.($query ? '?'.$query : null)), 301);
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
