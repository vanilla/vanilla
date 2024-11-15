<?php
/**
 * VanillaHooks Plugin
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.0
 * @package Vanilla
 */

use Garden\Container\Container;
use Garden\Container\Reference;
use Vanilla\DiscussionTypeHandler;
use Vanilla\Forum\Menu\CommunityManagementCounterProvider;
use Vanilla\Theme\BoxThemeShim;
use Vanilla\Theme\ThemeSectionModel;

/**
 * Vanilla's event handlers.
 */
class VanillaHooks extends Gdn_Plugin
{
    /**
     * Handle the container init event to register things with the container.
     *
     * @param Container $dic
     */
    public function container_init(Container $dic)
    {
        $dic->rule(\Vanilla\Navigation\BreadcrumbModel::class)
            ->addCall("addProvider", [new Reference(\Vanilla\Forum\Navigation\ForumBreadcrumbProvider::class)])

            ->rule(\Vanilla\Menu\CounterModel::class)
            ->addCall("addProvider", [new Reference(\Vanilla\Forum\Menu\ForumCounterProvider::class)])
            ->addCall("addProvider", [new Reference(CommunityManagementCounterProvider::class)])

            ->rule(ThemeSectionModel::class)
            ->addCall("registerLegacySection", [t("Forum")])

            ->rule(\Vanilla\DiscussionTypeConverter::class)
            ->addCall("addTypeHandler", [new Reference(DiscussionTypeHandler::class)])

            ->rule(PermissionModel::class)
            ->addCall("addJunctionModel", ["Category", new Reference(CategoryModel::class)]);

        $mf = \Vanilla\Models\ModelFactory::fromContainer($dic);
        $mf->addModel("category", CategoryModel::class, "cat");
        $mf->addModel("discussion", DiscussionModel::class, "d");
        $mf->addModel("comment", CommentModel::class, "c");
    }

    /**
     * Add to valid media attachment types.
     *
     * @param \Garden\Schema\Schema $schema
     */
    public function articlesPatchAttachmentSchema_init(\Garden\Schema\Schema $schema)
    {
        $types = $schema->getField("properties.foreignType.enum");
        $types[] = "comment";
        $types[] = "discussion";
        $schema->setField("properties.foreignType.enum", $types);
    }

    /**
     * Verify the current user can attach a media item to a Vanilla post.
     *
     * @param bool $canAttach
     * @param string $foreignType
     * @param int $foreignID
     * @return bool
     */
    public function canAttachMedia_handler(bool $canAttach, string $foreignType, int $foreignID): bool
    {
        switch ($foreignType) {
            case "comment":
                $model = new CommentModel();
                break;
            case "discussion":
                $model = new DiscussionModel();
                break;
            default:
                return $canAttach;
        }

        $row = $model->getID($foreignID, DATASET_TYPE_ARRAY);
        if (!$row) {
            return false;
        }
        return $row["InsertUserID"] === Gdn::session()->UserID ||
            Gdn::session()->checkRankedPermission("Garden.Moderation.Manage");
    }

    /**
     * Counter rebuilding.
     *
     * @param DbaController $sender
     */
    public function dbaController_countJobs_handler($sender)
    {
        $counts = [
            "Discussion" => [
                "CountComments",
                "FirstCommentID",
                "LastCommentID",
                "DateLastComment",
                "LastCommentUserID",
            ],
            "Category" => [
                "CountChildCategories",
                "CountDiscussions",
                "CountAllDiscussions",
                "CountComments",
                "CountAllComments",
                "LastPost",
            ],
            "Tag" => ["CountDiscussions"],
            "User" => ["CountDiscussions", "CountComments"],
        ];

        foreach ($counts as $table => $columns) {
            foreach ($columns as $column) {
                $name = "Recalculate $table.$column";
                $url = "/dba/counts.json?" . http_build_query(["table" => $table, "column" => $column]);
                $sender->Data["Jobs"][$name] = $url;
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
    public function deleteUserData($userID, $options = [], &$data = null)
    {
        $sql = Gdn::sql();

        $deleteMethod = $options["DeleteMethod"] ?? false;
        $isMethodKeep = $deleteMethod && $deleteMethod === "keep";
        if (!$isMethodKeep) {
            Gdn::userModel()->getDelete("UserPoints", ["UserID" => $userID], $data);
        }

        // Remove discussion watch records and drafts.
        $sql->delete("UserDiscussion", ["UserID" => $userID]);

        Gdn::userModel()->getDelete("Draft", ["InsertUserID" => $userID], $data);

        // Comment deletion depends on method selected
        $deleteMethod = val("DeleteMethod", $options, "delete");
        if ($deleteMethod == "delete") {
            // Get a list of category IDs that has this user as the most recent poster.
            $discussionCats = $sql
                ->select("cat.CategoryID")
                ->from("Category cat")
                ->join("Discussion d", "d.DiscussionID = cat.LastDiscussionID")
                ->where("d.InsertUserID", $userID)
                ->get()
                ->resultArray();

            $commentCats = $sql
                ->select("cat.CategoryID")
                ->from("Category cat")
                ->join("Comment c", "c.CommentID = cat.LastCommentID")
                ->where("c.InsertUserID", $userID)
                ->get()
                ->resultArray();

            $categoryIDs = array_unique(
                array_merge(array_column($discussionCats, "CategoryID"), array_column($commentCats, "CategoryID"))
            );

            // Grab all of the discussions that the user has engaged in.
            $discussionIDs = $sql
                ->select("DiscussionID")
                ->from("Comment")
                ->where("InsertUserID", $userID)
                ->groupBy("DiscussionID")
                ->get()
                ->resultArray();
            $discussionIDs = array_column($discussionIDs, "DiscussionID");

            Gdn::userModel()->getDelete("Comment", ["InsertUserID" => $userID], $data);

            // Update the comment counts.
            $commentCounts = $sql
                ->select("DiscussionID")
                ->select("CommentID", "count", "CountComments")
                ->select("CommentID", "max", "LastCommentID")
                ->whereIn("DiscussionID", $discussionIDs)
                ->groupBy("DiscussionID")
                ->get("Comment")
                ->resultArray();

            foreach ($commentCounts as $row) {
                $sql->put(
                    "Discussion",
                    ["CountComments" => $row["CountComments"] + 1, "LastCommentID" => $row["LastCommentID"]],
                    ["DiscussionID" => $row["DiscussionID"]]
                );
            }

            // Update the last user IDs.
            $sql->update("Discussion d")
                ->join("Comment c", "d.LastCommentID = c.CommentID", "left")
                ->set("d.LastCommentUserID", "c.InsertUserID", false, false)
                ->set("d.DateLastComment", "coalesce(c.DateInserted, d.DateInserted)", false, false)
                ->whereIn("d.DiscussionID", $discussionIDs)
                ->put();

            // Update the last posts.
            $discussions = $sql
                ->whereIn("DiscussionID", $discussionIDs)
                ->where("LastCommentUserID", $userID)
                ->get("Discussion");

            // Delete the user's discussions.
            Gdn::userModel()->getDelete("Discussion", ["InsertUserID" => $userID], $data);

            // Update the appropriate recent posts in the categories.
            $categoryModel = new CategoryModel();
            foreach ($categoryIDs as $categoryID) {
                $categoryModel->refreshAggregateRecentPost($categoryID, false);
            }
        } elseif ($deleteMethod == "wipe") {
            // Erase the user's discussions.
            $sql->update("Discussion")
                ->set("Body", t("The user and all related content has been deleted."))
                ->set("Format", "Deleted")
                ->where("InsertUserID", $userID)
                ->put();

            $sql->update("Comment")
                ->set("Body", t("The user and all related content has been deleted."))
                ->set("Format", "Deleted")
                ->where("InsertUserID", $userID)
                ->put();
        } else {
            // Leave comments
        }

        // Remove the user's profile information related to this application
        $sql->update("User")
            ->set([
                "CountDiscussions" => 0,
                "CountUnreadDiscussions" => 0,
                "CountComments" => 0,
                "CountDrafts" => 0,
                "CountBookmarks" => 0,
            ])
            ->where("UserID", $userID)
            ->put();
    }

    /**
     * Add tag data to discussions.
     *
     * @param DiscussionController $sender
     */
    public function discussionController_render_before($sender)
    {
        $discussionID = $sender->data("Discussion.DiscussionID");
        if ($discussionID) {
            // Get the tags on this discussion.
            $tags = TagModel::instance()->getDiscussionTags($discussionID, TagModel::IX_EXTENDED);

            foreach ($tags as $key => $value) {
                $sender->setData("Discussion." . $key, $value);
            }
        }

        $OrderBy = ReactionModel::commentOrder();
        [$OrderColumn, $OrderDirection] = explode(" ", val("0", $OrderBy));
        $OrderColumn = stringBeginsWith($OrderColumn, "c.", true, true);

        // Send back comment order for non-api calls.
        if ($sender->deliveryType() !== DELIVERY_TYPE_DATA) {
            $sender->setData("CommentOrder", ["Column" => $OrderColumn, "Direction" => $OrderDirection]);
        }

        $sender->addJsFile("jquery-ui.min.js");
        $sender->addJsFile("reactions.js", "vanilla");

        $ReactionModel = new ReactionModel();
        if (
            checkPermission("Garden.Reactions.View") &&
            Gdn::config("Vanilla.Reactions.ShowUserReactions", ReactionModel::RECORD_REACTIONS_DEFAULT) == "avatars"
        ) {
            $ReactionModel->joinUserTags($sender->Data["Discussion"], "Discussion");
            $ReactionModel->joinUserTags($sender->Data["Comments"], "Comment");

            if (isset($sender->Data["Answers"])) {
                $ReactionModel->joinUserTags($sender->Data["Answers"], "Comment");
            }
        }

        include_once $sender->fetchViewLocation("reaction_functions", "reactions", "dashboard");
    }

    /**
     *
     *
     * @param DiscussionController $sender
     */
    public function discussionController_beforeCommentBody_handler($sender)
    {
        Gdn::regarding()->beforeCommentBody($sender);
    }

    /**
     * Show tags after discussion body.
     *
     * @param DiscussionController $sender
     */
    public function discussionController_afterDiscussionBody_handler($sender)
    {
        /*  */
        // Allow disabling of inline tags.
        if (!c("Vanilla.Tagging.DisableInline", false)) {
            if (!property_exists($sender->EventArguments["Object"], "CommentID")) {
                $discussionID = property_exists($sender, "DiscussionID") ? $sender->DiscussionID : 0;

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
    public function discussionModel_beforeSaveDiscussion_handler($sender, $args)
    {
        // Allow an addon to set disallowed tag names. Reaction types are reserved by default.
        $reservedTags = array_keys(ReactionModel::reactionTypes());
        $sender->EventArguments["ReservedTags"] = &$reservedTags;
        $sender->fireEvent("ReservedTags");

        // Set some tagging requirements.
        $tagsString = trim(strtolower(valr("FormPostValues.Tags", $args, "")));
        if (stringIsNullOrEmpty($tagsString) && c("Vanilla.Tagging.Required")) {
            $sender->Validation->addValidationResult("Tags", "You must specify at least one tag.");
        } else {
            // Break apart our tags and lowercase them all for comparisons.
            $tags = TagModel::splitTags($tagsString);
            $tags = array_map("strtolower", $tags);
            /** @psalm-suppress EmptyArrayAccess $reservedTags */
            $reservedTags = array_map("strtolower", $reservedTags);
            $maxTags = c("Vanilla.Tagging.Max", 5);

            // Validate our tags.
            if ($reservedTags = array_intersect($tags, $reservedTags)) {
                $names = implode(", ", $reservedTags);
                $sender->Validation->addValidationResult(
                    "Tags",
                    "@" . sprintf(t("These tags are reserved and cannot be used: %s"), $names)
                );
            }
            if (!TagModel::validateTags($tags)) {
                $sender->Validation->addValidationResult(
                    "Tags",
                    "@" . t("ValidateTag", "Tags cannot contain commas or underscores.")
                );
            }
            if (count($tags) > $maxTags) {
                $sender->Validation->addValidationResult(
                    "Tags",
                    "@" . sprintf(t("You can only specify up to %s tags."), $maxTags)
                );
            }
        }
    }

    /**
     * Save tags when saving a discussion.
     *
     * @param DiscussionModel $sender
     */
    public function discussionModel_afterSaveDiscussion_handler($sender)
    {
        $formPostValues = val("FormPostValues", $sender->EventArguments, []);
        $categoryID = valr("Fields.CategoryID", $sender->EventArguments, false);
        $rawFormTags = val("Tags", $formPostValues, "");
        $newDiscussion = $formPostValues["IsNewDiscussion"] ?? false;

        $this->restoreTags($formPostValues, $categoryID, $rawFormTags, $newDiscussion);
    }

    /**
     * Handle saving tags when restoring a discussion.
     *
     * @param $sender
     * @param $discussion
     * @return void
     */
    public function discussionModel_afterRestoreDiscussion_handler(DiscussionModel $sender, array $discussion): void
    {
        $categoryID = $discussion["CategoryID"] ?? 0;
        $rawFormTags = $discussion["Tags"] ?? "";
        $this->restoreTags($discussion, $categoryID, $rawFormTags);
    }

    /**
     * Save the tags for a restored discussion.
     *
     * @param array $discussion
     * @param int $categoryID
     * @param string $rawFormTags
     * @param bool $newDiscussion
     * @return void
     */
    private function restoreTags(
        array $discussion,
        int $categoryID,
        string $rawFormTags,
        bool $newDiscussion = false
    ): void {
        $formTags = TagModel::splitTags($rawFormTags);
        $discussionID = $discussion["DiscussionID"] ?? 0;

        // Don't change tags if there's no "Tags" field (this prevents tags from being lost when moving discussion to
        // a new category).
        if (empty($formTags) && !$newDiscussion) {
            return;
        }

        // Let plugins have their information getting saved.
        $types = [""];

        // We fire as TaggingPlugin since this code was taken from the old TaggingPlugin and we do not
        // want to break any hooks
        Gdn::pluginManager()
            ->fireAs("TaggingPlugin")
            ->fireEvent("SaveDiscussion", [
                "Data" => $discussion,
                "Tags" => &$formTags,
                "Types" => &$types,
                "CategoryID" => $categoryID,
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
    public function discussionModel_deleteDiscussion_handler($sender)
    {
        // Get discussionID that is being deleted
        $discussionID = $sender->EventArguments["DiscussionID"];

        // Get List of tags to reduce count for
        $tagDataSet = Gdn::sql()
            ->select("TagID")
            ->from("TagDiscussion")
            ->where("DiscussionID", $discussionID)
            ->get()
            ->resultArray();

        $removedTagIDs = array_column($tagDataSet, "TagID");

        // Check if there are even any tags to delete
        if (count($removedTagIDs) > 0) {
            // Step 1: Reduce count
            Gdn::sql()
                ->update("Tag")
                ->set("CountDiscussions", "CountDiscussions - 1", false)
                ->whereIn("TagID", $removedTagIDs)
                ->put();

            // Step 2: Delete mapping data between discussion and tag (tagdiscussion table)
            $sender->SQL->where("DiscussionID", $discussionID)->delete("TagDiscussion");
        }
    }

    /**
     * Add the tag input to the discussion form.
     *
     * @param Gdn_Controller $Sender
     */
    public function postController_afterDiscussionFormOptions_handler($Sender)
    {
        if (!c("Tagging.Discussions.Enabled")) {
            return;
        }

        if (in_array($Sender->RequestMethod, ["discussion", "editdiscussion", "question", "idea"])) {
            // Setup, get most popular tags
            $TagModel = TagModel::instance();
            $Tags = $TagModel
                ->getWhere(
                    ["Type" => array_keys($TagModel->defaultTypes())],
                    "CountDiscussions",
                    "desc",
                    c("Vanilla.Tagging.ShowLimit", 50)
                )
                ->result(DATASET_TYPE_ARRAY);
            $TagsHtml = count($Tags) ? "" : t("No tags have been created yet.");
            $Tags = Gdn_DataSet::index($Tags, "FullName");
            ksort($Tags);

            // The tags must be fetched.
            if ($Sender->Request->isPostBack()) {
                $tag_ids = TagModel::splitTags($Sender->Form->getFormValue("Tags"));
                $tags = TagModel::instance()
                    ->getWhere(["TagID" => $tag_ids])
                    ->resultArray();
                $tags = array_column($tags, "TagID", "FullName");
            } else {
                // The tags should be set on the data.
                $tags = array_column($Sender->data("Tags", []), "FullName", "TagID");
                $xtags = $Sender->data("XTags", []);
                foreach (TagModel::instance()->getAllowedTagTypes() as $key) {
                    if (isset($xtags[$key])) {
                        $xtags2 = array_column($xtags[$key], "FullName", "TagID");
                        foreach ($xtags2 as $id => $name) {
                            $tags[$id] = $name;
                        }
                    }
                }
            }

            echo '<div class="Form-Tags P">';

            // Tag text box
            echo $Sender->Form->label("Tags", "Tags");
            echo $Sender->Form->textBox("Tags", ["data-tags" => json_encode($tags)]);

            // Available tags
            echo wrap(anchor(t("Show popular tags"), "#"), "span", ["class" => "ShowTags"]);
            foreach ($Tags as $Tag) {
                $TagsHtml .=
                    anchor(htmlspecialchars($Tag["FullName"]), "#", "AvailableTag", [
                        "data-name" => $Tag["Name"],
                        "data-id" => $Tag["TagID"],
                    ]) . " ";
            }
            echo wrap($TagsHtml, "div", ["class" => "Hidden AvailableTags"]);

            echo "</div>";
        }
    }

    /**
     * Add javascript to the post/edit discussion page so that tagging autocomplete works.
     *
     * @param PostController $Sender
     */
    public function postController_render_before($Sender)
    {
        $Sender->addDefinition("TaggingAdd", Gdn::session()->checkPermission("Vanilla.Tagging.Add"));
        $Sender->addDefinition("TaggingSearchUrl", Gdn::request()->url("tags/search"));
        $Sender->addDefinition("MaxTagsAllowed", c("Vanilla.Tagging.Max", 5));

        // Make sure that detailed tag data is available to the form.
        $TagModel = TagModel::instance();

        $DiscussionID = $Sender->data("Discussion.DiscussionID");

        if ($DiscussionID) {
            $Tags = $TagModel->getDiscussionTags($DiscussionID, TagModel::IX_EXTENDED);
            $Sender->setData($Tags);
        } elseif (!$Sender->Request->isPostBack() && ($tagString = $Sender->Request->get("tags"))) {
            $tags = explodeTrim(",", $tagString);
            $types = array_column(TagModel::instance()->defaultTypes(), "key");

            // Look up the tags by name.
            $tagData = Gdn::sql()
                ->getWhere("Tag", ["Name" => $tags, "Type" => $types])
                ->resultArray();

            // Add any missing tags.
            $tagNames = array_change_key_case(array_column($tagData, "Name", "Name"));
            foreach ($tags as $tag) {
                $tagKey = strtolower($tag);
                if (!isset($tagNames[$tagKey])) {
                    $tagData[] = ["TagID" => $tag, "Name" => $tagKey, "FullName" => $tag, "Type" => ""];
                }
            }

            $Sender->setData("Tags", $tagData);
        }
    }

    /**
     * Provide default permissions for roles, based on the value in their Type column.
     *
     * @param PermissionModel $sender Instance of permission model that fired the event
     */
    public function permissionModel_defaultPermissions_handler($sender)
    {
        // Guest defaults
        $sender->addDefault(RoleModel::TYPE_GUEST, [
            "Vanilla.Discussions.View" => 1,
        ]);
        $sender->addDefault(
            RoleModel::TYPE_GUEST,
            [
                "Vanilla.Discussions.View" => 1,
            ],
            "Category",
            -1
        );

        // Unconfirmed defaults
        $sender->addDefault(RoleModel::TYPE_UNCONFIRMED, [
            "Vanilla.Discussions.View" => 1,
        ]);
        $sender->addDefault(
            RoleModel::TYPE_UNCONFIRMED,
            [
                "Vanilla.Discussions.View" => 1,
            ],
            "Category",
            -1
        );

        // Applicant defaults
        $sender->addDefault(RoleModel::TYPE_APPLICANT, [
            "Vanilla.Discussions.View" => 1,
        ]);
        $sender->addDefault(
            RoleModel::TYPE_APPLICANT,
            [
                "Vanilla.Discussions.View" => 1,
            ],
            "Category",
            -1
        );

        // Member defaults
        $sender->addDefault(RoleModel::TYPE_MEMBER, [
            "Vanilla.Discussions.Add" => 1,
            "Vanilla.Discussions.View" => 1,
            "Vanilla.Comments.Add" => 1,
        ]);
        $sender->addDefault(
            RoleModel::TYPE_MEMBER,
            [
                "Vanilla.Discussions.Add" => 1,
                "Vanilla.Discussions.View" => 1,
                "Vanilla.Comments.Add" => 1,
            ],
            "Category",
            -1
        );

        // Moderator defaults
        $sender->addDefault(RoleModel::TYPE_MODERATOR, [
            "Vanilla.Discussions.Add" => 1,
            "Vanilla.Discussions.Edit" => 1,
            "Vanilla.Discussions.Announce" => 1,
            "Vanilla.Discussions.Sink" => 1,
            "Vanilla.Discussions.Close" => 1,
            "Vanilla.Discussions.Delete" => 1,
            "Vanilla.Discussions.View" => 1,
            "Vanilla.Comments.Add" => 1,
            "Vanilla.Comments.Edit" => 1,
            "Vanilla.Comments.Delete" => 1,
            "Vanilla.Posts.Moderate" => 1,
        ]);
        $sender->addDefault(
            RoleModel::TYPE_MODERATOR,
            [
                "Vanilla.Discussions.Add" => 1,
                "Vanilla.Discussions.Edit" => 1,
                "Vanilla.Discussions.Announce" => 1,
                "Vanilla.Discussions.Sink" => 1,
                "Vanilla.Discussions.Close" => 1,
                "Vanilla.Discussions.Delete" => 1,
                "Vanilla.Discussions.View" => 1,
                "Vanilla.Comments.Add" => 1,
                "Vanilla.Comments.Edit" => 1,
                "Vanilla.Comments.Delete" => 1,
                "Vanilla.Posts.Moderate" => 1,
            ],
            "Category",
            -1
        );

        // Administrator defaults
        $sender->addDefault(RoleModel::TYPE_ADMINISTRATOR, [
            "Vanilla.Discussions.Add" => 1,
            "Vanilla.Discussions.Edit" => 1,
            "Vanilla.Discussions.Announce" => 1,
            "Vanilla.Discussions.Sink" => 1,
            "Vanilla.Discussions.Close" => 1,
            "Vanilla.Discussions.Delete" => 1,
            "Vanilla.Discussions.View" => 1,
            "Vanilla.Comments.Add" => 1,
            "Vanilla.Comments.Edit" => 1,
            "Vanilla.Comments.Delete" => 1,
            "Vanilla.Posts.Moderate" => 1,
        ]);
        $sender->addDefault(
            RoleModel::TYPE_ADMINISTRATOR,
            [
                "Vanilla.Discussions.Add" => 1,
                "Vanilla.Discussions.Edit" => 1,
                "Vanilla.Discussions.Announce" => 1,
                "Vanilla.Discussions.Sink" => 1,
                "Vanilla.Discussions.Close" => 1,
                "Vanilla.Discussions.Delete" => 1,
                "Vanilla.Discussions.View" => 1,
                "Vanilla.Comments.Add" => 1,
                "Vanilla.Comments.Edit" => 1,
                "Vanilla.Comments.Delete" => 1,
                "Vanilla.Posts.Moderate" => 1,
            ],
            "Category",
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
    public function userModel_beforeDeleteUser_handler($sender)
    {
        $userID = val("UserID", $sender->EventArguments);
        $options = val("Options", $sender->EventArguments, []);
        $options = is_array($options) ? $options : [];
        $content = &$sender->EventArguments["Content"];
        $this->deleteUserData($userID, $options, $content);
    }

    /**
     * Add CSS assets to front end.
     *
     * @param AssetModel $sender
     */
    public function assetModel_afterGetCssFiles_handler($sender)
    {
        if (!inSection("Dashboard")) {
            $sender->addCssFile("tag.css", "vanilla", ["Sort" => 800]);
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
    public function base_render_before($sender)
    {
        if ($sender->Menu) {
            $sender->Menu->addLink("Discussions", t("Discussions"), "/discussions", false, ["Standard" => true]);
            if (Gdn::config("Vanilla.Reactions.ShowBestOf")) {
                $sender->Menu->addLink("BestOf", t("Best Of..."), "/bestof/everything", false, ["class" => "BestOf"]);
            }
        }
        if (!isMobile()) {
            if (checkPermission("Garden.Reactions.View")) {
                $sender->addDefinition(
                    "ShowUserReactions",
                    Gdn::config("Vanilla.Reactions.ShowUserReactions", ReactionModel::RECORD_REACTIONS_DEFAULT)
                );
            }
        }

        if (!inSection("Dashboard")) {
            // Spoilers assets
            $sender->addJsFile("spoilers.js", "vanilla");
            $sender->addCssFile("spoilers.css", "vanilla");
            $sender->addDefinition("Spoiler", t("Spoiler"));
            $sender->addDefinition("show", t("show"));
            $sender->addDefinition("hide", t("hide"));
        }

        // Add user's viewable roles to gdn.meta if user is logged in.
        if (!$sender->addDefinition("Roles")) {
            if (Gdn::session()->isValid()) {
                $roleModel = new RoleModel();
                Gdn::controller()->addDefinition(
                    "Roles",
                    $roleModel->getPublicUserRoles(Gdn::session()->UserID, "Name")
                );
            }
        }

        // Tagging BEGIN
        // Set breadcrumbs where relevant
        if (null !== $sender->data("Tag", null) && null !== $sender->data("Tags")) {
            $parentTag = [];
            $currentTag = $sender->data("Tag");
            $currentTags = $sender->data("Tags");

            $parentTagID = $currentTag["ParentTagID"] ? $currentTag["ParentTagID"] : "";

            foreach ($currentTags as $tag) {
                foreach ($tag as $subTag) {
                    if ($subTag["TagID"] == $parentTagID) {
                        $parentTag = $subTag;
                    }
                }
            }

            $breadcrumbs = [];

            if (is_array($parentTag) && count(array_filter($parentTag))) {
                $breadcrumbs[] = ["Name" => $parentTag["FullName"], "Url" => tagUrl($parentTag, "", "/")];
            }

            if (is_array($currentTag) && count(array_filter($currentTag))) {
                $breadcrumbs[] = ["Name" => $currentTag["FullName"], "Url" => tagUrl($currentTag, "", "/")];
            }

            if (count($breadcrumbs)) {
                // Rebuild breadcrumbs in discussions when there is a child, as the
                // parent must come before it.
                $sender->setData("Breadcrumbs", $breadcrumbs);
            }
        }

        if (null !== $sender->data("Announcements", null)) {
            TagModel::instance()->joinTags($sender->Data["Announcements"]);
        }

        if (null !== $sender->data("Discussions", null)) {
            TagModel::instance()->joinTags($sender->Data["Discussions"]);
        }

        $sender->addJsFile("tagging.js", "vanilla");
        $sender->addJsFile("jquery.tokeninput.js");
        // Tagging END
    }

    /**
     * Adds 'Discussions' tab to profiles and adds CSS & JS files to their head.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param ProfileController $sender
     */
    public function profileController_addProfileTabs_handler($sender)
    {
        if (is_object($sender->User) && $sender->User->UserID > 0) {
            $userID = $sender->User->UserID;
            // Add the discussion tab
            $discussionsLabel = sprite("SpDiscussions") . " " . t("Discussions");
            $commentsLabel = sprite("SpComments") . " " . t("Comments");
            if (c("Vanilla.Profile.ShowCounts", true)) {
                $discussionsCount = getValueR("User.CountDiscussions", $sender, null);
                $commentsCount = getValueR("User.CountComments", $sender, null);

                if (!is_null($discussionsCount) && !empty($discussionsCount)) {
                    $discussionsLabel .=
                        '<span class="Aside">' .
                        countString(
                            bigPlural($discussionsCount, "%s discussion"),
                            "/profile/count/discussions?userid=$userID"
                        ) .
                        "</span>";
                }
                if (!is_null($commentsCount) && !empty($commentsCount)) {
                    $commentsLabel .=
                        '<span class="Aside">' .
                        countString(bigPlural($commentsCount, "%s comment"), "/profile/count/comments?userid=$userID") .
                        "</span>";
                }
            }
            $sender->addProfileTab(
                t("Discussions"),
                userUrl($sender->User, "", "discussions"),
                "Discussions",
                $discussionsLabel
            );
            $sender->addProfileTab(t("Comments"), userUrl($sender->User, "", "comments"), "Comments", $commentsLabel);
            // Add the discussion tab's CSS and Javascript.
            $sender->addJsFile("jquery.gardenmorepager.js");
            $sender->addJsFile("discussions.js", "vanilla");
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
    public function profileController_afterPreferencesDefined_handler($sender)
    {
        $sender->Preferences["Notifications"]["Email.DiscussionComment"] = t(
            "Notify me when people comment on my discussions."
        );
        $sender->Preferences["Notifications"]["Email.BookmarkComment"] = t(
            "Notify me when people comment on my bookmarked discussions."
        );
        $sender->Preferences["Notifications"]["Email.Mention"] = t("Notify me when people mention me.");
        $sender->Preferences["Notifications"]["Email.ParticipateComment"] = t(
            'Notify me when people comment on discussions I\'ve participated in.'
        );

        $sender->Preferences["Notifications"]["Popup.DiscussionComment"] = t(
            "Notify me when people comment on my discussions."
        );
        $sender->Preferences["Notifications"]["Popup.BookmarkComment"] = t(
            "Notify me when people comment on my bookmarked discussions."
        );
        $sender->Preferences["Notifications"]["Popup.Mention"] = t("Notify me when people mention me.");
        $sender->Preferences["Notifications"]["Popup.ParticipateComment"] = t(
            'Notify me when people comment on discussions I\'ve participated in.'
        );
    }

    /**
     * @param NavModule $sender
     */
    public function siteNavModule_init_handler($sender)
    {
        // Grab the default route so that we don't add a link to it twice.
        $home = trim(val("Destination", Gdn::router()->getRoute("DefaultController")), "/");

        // Add the site discussion links.
        $sender->addLinkIf(
            $home !== "categories",
            t("All Categories", "Categories"),
            "/categories",
            "main.categories",
            "",
            1,
            ["icon" => "th-list"]
        );
        $sender->addLinkIf(
            $home !== "discussions",
            t("Recent Discussions"),
            "/discussions",
            "main.discussions",
            "",
            1,
            ["icon" => "discussion"]
        );
        $sender->addGroup(t("Favorites"), "favorites", "", 3);

        if (Gdn::session()->isValid()) {
            $sender->addLink(
                t("My Bookmarks"),
                "/discussions/bookmarked",
                "favorites.bookmarks",
                "",
                [],
                ["icon" => "star", "badge" => Gdn::session()->User->CountBookmarks]
            );
            $sender->addLink(
                t("My Discussions"),
                "/discussions/mine",
                "favorites.discussions",
                "",
                [],
                ["icon" => "discussion", "badge" => Gdn::session()->User->CountDiscussions]
            );
            $sender->addLink(
                t("Drafts"),
                "/drafts",
                "favorites.drafts",
                "",
                [],
                ["icon" => "compose", "badge" => Gdn::session()->User->CountDrafts]
            );
        }

        $user = Gdn::controller()->data("Profile");
        if (!$user) {
            return;
        }
        $sender->addGroupToSection("Profile", t("Posts"), "posts");
        $sender->addLinkToSection(
            "Profile",
            t("Discussions"),
            userUrl($user, "", "discussions"),
            "posts.discussions",
            "",
            [],
            ["icon" => "discussion", "badge" => val("CountDiscussions", $user)]
        );
        $sender->addLinkToSection(
            "Profile",
            t("Comments"),
            userUrl($user, "", "comments"),
            "posts.comments",
            "",
            [],
            ["icon" => "comment", "badge" => val("CountComments", $user)]
        );
    }

    /**
     * Creates virtual 'Comments' method in ProfileController.
     *
     * @since 2.0.0
     * @package Vanilla
     *
     * @param ProfileController $sender ProfileController.
     */
    public function profileController_comments_create(
        $sender,
        $userReference = "",
        $username = "",
        $page = "",
        $userID = ""
    ) {
        $sender->permission("Garden.Profiles.View");

        $sender->editMode(false);
        $view = $sender->View;

        // Tell the ProfileController what tab to load
        $sender->getUserInfo($userReference, $username, $userID);
        $sender->_setBreadcrumbs(t("Comments"), userUrl($sender->User, "", "comments"));
        $sender->setTabView("Comments", "profile", "Discussion", "Vanilla");

        $pageSize = c("Vanilla.Discussions.PerPage", 30);
        [$offset, $limit] = offsetLimit($page, $pageSize);

        $commentModel = new CommentModel();

        $where = [
            "c.InsertUserID" => $sender->User->UserID,
        ];
        if ($lastCommentID = $sender->Request->get("lid")) {
            $where["c.CommentID <"] = $lastCommentID;
        }
        /** @var Gdn_DataSet $comments */
        $comments = $commentModel->getWhere($where, "CommentID", "DESC", $limit, $offset);
        $totalRecords = $offset + $comments->count() + 1;

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $sender->Pager = $pagerFactory->getPager("MorePager", $sender);
        $sender->Pager->MoreCode = "More Comments";
        $sender->Pager->LessCode = "Newer Comments";
        $sender->Pager->ClientID = "Pager";
        $sender->Pager->configure(
            $offset,
            $limit,
            $totalRecords,
            userUrl($sender->User, "", "comments") . "?page={Page}" //?lid='.$CommentModel->LastCommentID
        );

        // Deliver JSON data if necessary
        if ($sender->deliveryType() != DELIVERY_TYPE_ALL && $offset > 0) {
            $sender->setJson("LessRow", $sender->Pager->toString("less"));
            $sender->setJson("MoreRow", $sender->Pager->toString("more"));
            $sender->View = "profilecomments";
        }
        $sender->setData("Comments", $comments);
        $sender->setData("UnfilteredCommentsCount", $comments->count());

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show discussion options
        $sender->ShowOptions = false;

        if ($sender->Head) {
            $sender->Head->addTag("meta", ["name" => "robots", "content" => "noindex,noarchive"]);
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
    public function profileController_discussions_create(
        $sender,
        $userReference = "",
        $username = "",
        $page = "",
        $userID = ""
    ) {
        $sender->permission("Garden.Profiles.View");

        $sender->editMode(false);

        // Tell the ProfileController what tab to load
        $sender->getUserInfo($userReference, $username, $userID);
        $sender->_setBreadcrumbs(t("Discussions"), userUrl($sender->User, "", "discussions"));
        $sender->setTabView("Discussions", "Profile", "Discussions", "Vanilla");
        $sender->CountCommentsPerPage = c("Vanilla.Comments.PerPage", 30);

        [$offset, $limit] = offsetLimit($page, c("Vanilla.Discussions.PerPage", 30));

        $discussionModel = new DiscussionModel();
        $discussions = $discussionModel->getByUser(
            $sender->User->UserID,
            $limit,
            $offset,
            false,
            Gdn::session()->UserID,
            "PermsDiscussionsView"
        );
        $countDiscussions = $offset + $discussionModel->LastDiscussionCount + 1;

        $sender->setData("UnfilteredDiscussionsCount", $discussionModel->LastDiscussionCount);
        $sender->DiscussionData = $sender->setData("Discussions", $discussions);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $sender->Pager = $pagerFactory->getPager("MorePager", $sender);
        $sender->Pager->MoreCode = "More Discussions";
        $sender->Pager->LessCode = "Newer Discussions";
        $sender->Pager->ClientID = "Pager";
        $sender->Pager->configure(
            $offset,
            $limit,
            $countDiscussions,
            userUrl($sender->User, "", "discussions") . "?page={Page}"
        );

        // Deliver JSON data if necessary
        if ($sender->deliveryType() != DELIVERY_TYPE_ALL && $offset > 0) {
            $sender->setJson("LessRow", $sender->Pager->toString("less"));
            $sender->setJson("MoreRow", $sender->Pager->toString("more"));
            $sender->View = "discussions";
        }

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show discussion options
        $sender->ShowOptions = false;

        if ($sender->Head) {
            // These pages offer only duplicate content to search engines and are a bit slow.
            $sender->Head->addTag("meta", ["name" => "robots", "content" => "noindex,noarchive"]);
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
    public function settingsController_defineAdminPermissions_handler($sender)
    {
        if (isset($sender->RequiredAdminPermissions)) {
            $sender->RequiredAdminPermissions[] = "Garden.Settings.Manage";
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
    public function dashboardNavModule_init_handler($sender)
    {
        $sort = -1; // Ensure these items go before any plugin items.

        $sender
            ->addLinkIf(
                "Garden.Community.Manage",
                t("Categories"),
                "/vanilla/settings/categories",
                "forum.manage-categories",
                "nav-manage-categories",
                $sort
            )
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("Posting"),
                "/vanilla/settings/posting",
                "forum.posting",
                "nav-forum-posting",
                $sort
            )
            ->addLinkIf(
                "Garden.Community.Manage",
                t("Reactions"),
                "reactions",
                "forum.reactions",
                "nav-reactions",
                $sort
            )
            ->addLinkIf(
                c("Vanilla.Archive.Date", false) && Gdn::session()->checkPermission("Garden.Settings.Manage"),
                t("Archive Discussions"),
                "/vanilla/settings/archive",
                "forum.archive",
                "nav-forum-archive",
                $sort
            )
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("Embedding"),
                "embed/forum",
                "site-settings.embed-site",
                "nav-embed nav-embed-site",
                $sort
            )
            ->addLinkToSectionIf(
                "Garden.Settings.Manage",
                "Moderation",
                t("Flood Control"),
                "/vanilla/settings/floodcontrol",
                "settings.flood-control",
                "nav-flood-control"
            );
    }

    /**
     * Handle post-restore operations from the log table.
     *
     * @param LogModel $sender
     * @param array $args
     */
    public function logModel_afterRestore_handler($sender, $args)
    {
        $recordType = valr("Log.RecordType", $args);
        $recordUserID = valr("Log.RecordUserID", $args);

        if ($recordUserID === false) {
            return;
        }

        switch ($recordType) {
            case "Comment":
                $commentModel = new CommentModel();
                $commentModel->updateUser($recordUserID, true);
                break;
            case "Discussion":
                $discussionModel = new DiscussionModel();
                $discussionModel->updateUserDiscussionCount($recordUserID, true);

                $discussionID = (int) $args["InsertID"] ?? ($args["Log"]["Data"]["RecordID"] ?? 0);
                $discussionData = $args["Log"]["Data"] ?? [];
                $discussionData["DiscussionID"] = $discussionID;
                $categoryID = $args["Log"]["CategoryID"] ?? 0;
                $rawFormTags = $args["Log"]["Data"]["Tags"] ?? "";

                $this->restoreTags($discussionData, $categoryID, $rawFormTags, isset($args["InsertID"]));
        }
    }

    /**
     * @deprecated Request /tags/search instead
     */
    public function pluginController_tagsearch_create()
    {
        $query = http_build_query(Gdn::request()->getQuery());
        redirectTo(url("/tags/search" . ($query ? "?" . $query : null)), 301);
    }

    /**
     * Hook in before a discussion is rendered and display any messages.
     *
     * @param mixed DiscussionController $sender
     * @param array array $args
     */
    public function discussionController_beforeDiscussionDisplay_handler($sender, array $args)
    {
        if (!($sender instanceof DiscussionController)) {
            return;
        }

        $messages = $sender->getMessages();
        foreach ($messages as $message) {
            echo $message;
        }
    }

    /**
     * Automatically executed when application is enabled.
     *
     * @since 2.0.0
     * @package Vanilla
     */
    public function setup()
    {
        $Database = Gdn::database();
        $Config = Gdn::factory(Gdn::AliasConfig);
        $Drop = false;

        // NOTE: Currently some structure elements don't occur the first time around
        // So the Vanilla addon actually slightly depends on this getting run twice.
        // If you remove this go take a look at the failures in AddonEnableDisableTest.

        // Call structure.php to update database
        $Validation = new Gdn_Validation(); // Needed by structure.php to validate permission names
        include PATH_APPLICATIONS . DS . "vanilla" . DS . "settings" . DS . "structure.php";

        saveToConfig("Routes.DefaultController", "discussions");
    }

    /**
     * Render the reactions on the profile.
     *
     * @param ProfileController $sender
     */
    public function profileController_render_before($sender)
    {
        if (!$sender->data("Profile")) {
            return;
        }

        // Grab all of the counts for the user.
        $data = Gdn::sql()
            ->getWhere("UserTag", [
                "RecordID" => $sender->data("Profile.UserID"),
                "RecordType" => "User",
                "UserID" => ReactionModel::USERID_OTHER,
            ])
            ->resultArray();
        $data = Gdn_DataSet::index($data, ["TagID"]);

        $counts = $sender->data("Counts", []);
        foreach (ReactionModel::reactionTypes() as $code => $type) {
            if (!$type["Active"]) {
                continue;
            }

            $row = [
                "Name" => $type["Name"],
                "Url" => url(
                    userUrl($sender->data("Profile"), "", "reactions") . "?reaction=" . urlencode($code),
                    true
                ),
                "Total" => 0,
            ];

            if (isset($data[$type["TagID"]])) {
                $row["Total"] = $data[$type["TagID"]]["Total"];
            }
            $counts[$type["Name"]] = $row;
        }

        $sender->setData("Counts", $counts);
        $sender->addJsFile("jquery-ui.min.js");
        $sender->addJsFile("reactions.js", "vanilla");
    }

    /**
     *
     *
     * @param ActivityController $sender
     */
    public function activityController_render_before($sender)
    {
        if ($sender->deliveryMethod() == DELIVERY_METHOD_XHTML || $sender->deliveryType() == DELIVERY_TYPE_VIEW) {
            $sender->addJsFile("jquery-ui.min.js");
            $sender->addJsFile("reactions.js", "vanilla");
            include_once $sender->fetchViewLocation("reaction_functions", "reactions", "dashboard");
        }
    }

    /**
     * Add the reactions CSS to the page.
     *
     * @param \Vanilla\Web\Asset\LegacyAssetModel $sender
     */
    public function assetModel_styleCss_handler($sender)
    {
        $sender->addCssFile("reactions.css", "vanilla");
    }

    /**
     * Handle user reactions.
     *
     * @param Gdn_Controller $Sender
     * @param string $RecordType Type of record we're reacting to. Discussion, comment or activity.
     * @param string $Reaction The url code of the reaction.
     * @param int $ID The ID of the record.
     * @param bool $selfReact Whether a user can react to their own post
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function rootController_react_create($Sender, $RecordType, $Reaction, $ID, $selfReact = false)
    {
        if (!Gdn::session()->isValid()) {
            throw new Gdn_UserException(t("You need to sign in before you can do this."), 403);
        }

        include_once $Sender->fetchViewLocation("reaction_functions", "reactions", "dashboard");

        if (!$Sender->Request->isAuthenticatedPostBack(true)) {
            throw permissionException("Javascript");
        }

        $ReactionType = ReactionModel::reactionTypes($Reaction);
        $Sender->EventArguments["ReactionType"] = &$ReactionType;
        $Sender->EventArguments["RecordType"] = $RecordType;
        $Sender->EventArguments["RecordID"] = $ID;
        $Sender->fireAs("ReactionModel")->fireEvent("GetReaction");

        // Only allow enabled reactions
        if (!val("Active", $ReactionType)) {
            throw forbiddenException("@You may not use that Reaction.");
        }

        // Permission
        if ($Permission = val("Permission", $ReactionType)) {
            // Check reaction's permission if a custom/specific one is applied
            $Sender->permission($Permission);
        } elseif ($PermissionClass = val("Class", $ReactionType)) {
            // Check reaction's permission based on class
            $Sender->permission("Reactions." . $PermissionClass . ".Add");
        }

        if (strtolower($RecordType) === "discussion") {
            $discussion = DiscussionModel::instance()->getID((int) $ID);
        } elseif (strtolower($RecordType) === "comment") {
            $comment = CommentModel::instance()->getID((int) $ID);
            $discussion = DiscussionModel::instance()->getID($comment->DiscussionID);
        }

        if ($discussion) {
            $eventManager = Gdn::eventManager();
            $eventManager->fire("reactionModel_beforeReact", $discussion);
            $category = CategoryModel::categories($discussion->CategoryID);
            $Sender->permission("Vanilla.Discussions.View", true, "Category", $category["PermissionCategoryID"]);
        }

        $ReactionModel = new ReactionModel();
        $ReactionModel->react($RecordType, $ID, $Reaction, null, $selfReact);
        $Sender->render("Blank", "Utility", "Dashboard");
    }

    /**
     * Add a "Best Of" view for reacted content.
     *
     * @param RootController $sender Controller firing the event.
     * @param string $reaction Type of reaction content to show
     * @param int $page The current page of content
     */
    public function rootController_bestOfOld_create($sender, $reaction = "everything")
    {
        // Load all of the reaction types.
        try {
            $reactionTypes = ReactionModel::getReactionTypes(["Class" => "Positive", "Active" => 1]);
            $sender->setData("ReactionTypes", $reactionTypes);
        } catch (Exception $ex) {
            $sender->setData("ReactionTypes", []);
        }
        if (!isset($reactionTypes[$reaction])) {
            $reaction = "everything";
        }
        $sender->setData("CurrentReaction", $reaction);

        // Define the query offset & limit.
        $page = "p" . getIncomingValue("Page", 1);
        $limit = c("Vanilla.Reactions.BestOfPerPage", 30);
        [$offset, $limit] = offsetLimit($page, $limit);
        $sender->setData("_Limit", $limit + 1);

        $reactionModel = new ReactionModel();
        if ($reaction === "everything") {
            $promotedTagID = $reactionModel->defineTag("Promoted", "BestOf");
            $data = $reactionModel->getRecordsWhere(
                ["TagID" => $promotedTagID, "RecordType" => ["Discussion", "Comment"]],
                "DateInserted",
                "desc",
                $limit + 1,
                $offset
            );
        } else {
            $reactionType = $reactionTypes[$reaction];
            $data = $reactionModel->getRecordsWhere(
                [
                    "TagID" => $reactionType["TagID"],
                    "RecordType" => ["Discussion-Total", "Comment-Total"],
                    "Total >=" => 1,
                ],
                "DateInserted",
                "desc",
                $limit + 1,
                $offset
            );
        }

        $sender->setData("_CurrentRecords", count($data));
        if (count($data) > $limit) {
            array_pop($data);
        }
        if (
            checkPermission("Garden.Reactions.View") &&
            Gdn::config("Vanilla.Reactions.ShowUserReactions", ReactionModel::RECORD_REACTIONS_DEFAULT) == "avatars"
        ) {
            $reactionModel->joinUserTags($data);
        }
        $sender->setData("Data", $data);

        // Set up head.
        $sender->Head = new HeadModule($sender);
        $sender->addJsFile("jquery.js");
        $sender->addJsFile("jquery.livequery.js");
        $sender->addJsFile("global.js");
        $sender->addJsFile("library/jQuery-Masonry/jquery.masonry.js", "vanilla"); // I customized this to get proper callbacks.
        $sender->addJsFile("library/jQuery-InfiniteScroll/jquery.infinitescroll.min.js", "vanilla");
        $sender->addJsFile("tile.js", "vanilla");
        $sender->addCssFile("style.css");
        $sender->addCssFile("vanillicon.css", "static");

        // Set the title, breadcrumbs, canonical.
        $sender->title(t("Best Of"));
        $sender->setData("Breadcrumbs", [["Name" => t("Best Of"), "Url" => "/bestof/everything"]]);
        $sender->canonicalUrl(
            url(
                concatSep("/", "bestof/" . $reaction, pageNumber($offset, $limit, true, Gdn::session()->UserID != 0)),
                true
            ),
            Gdn::session()->UserID == 0
        );

        // Modules
        $sender->addModule("GuestModule");
        $sender->addModule("SignedInModule");
        $sender->addModule("BestOfFilterModule");

        // Render the page.
        if (class_exists("LeaderBoardModule")) {
            $sender->addModule("LeaderBoardModule");

            $module = new LeaderBoardModule();
            $module->SlotType = "a";
            $sender->addModule($module);
        }

        // Render the page (or deliver the view)
        $sender->render("bestof_old", "reactions", "dashboard");
    }

    /**
     * Add a "Best Of" view for reacted content.
     *
     * @param RootController $sender Controller firing the event.
     * @param string $reaction Type of reaction content to show
     */
    public function rootController_bestOf_create($sender, $reaction = "everything")
    {
        Gdn_Theme::section("BestOf");
        // Load all of the reaction types.
        try {
            $reactionTypes = ReactionModel::getReactionTypes(["Class" => "Positive", "Active" => 1]);

            $sender->setData("ReactionTypes", $reactionTypes);
        } catch (Exception $ex) {
            $sender->setData("ReactionTypes", []);
        }

        if (!isset($reactionTypes[$reaction])) {
            $reaction = "everything";
        }
        $sender->setData("CurrentReaction", $reaction);

        // Define the query offset & limit.
        $page = Gdn::request()->get("Page", 1);

        // Limit the number of pages.
        if (ReactionModel::BEST_OF_MAX_PAGES && $page > ReactionModel::BEST_OF_MAX_PAGES) {
            $page = ReactionModel::BEST_OF_MAX_PAGES;
        }
        $page = "p" . $page;

        $limit = Gdn::config("Vanilla.Reactions.BestOfPerPage", 10);
        [$offset, $limit] = offsetLimit($page, $limit);

        $sender->setData("_Limit", $limit + 1);

        $reactionModel = new ReactionModel();
        Gdn::config()->set("Vanilla.Reactions.ShowUserReactions", false, false);
        if ($reaction == "everything") {
            $promotedTagID = $reactionModel->defineTag("Promoted", "BestOf");
            $reactionModel->fireEvent("BeforeGet", ["ApplyRestrictions" => true]);
            $data = $reactionModel->getRecordsWhere(
                ["TagID" => $promotedTagID, "RecordType" => ["Discussion", "Comment"]],
                "UserTag.DateInserted",
                "desc",
                $limit + 1,
                $offset
            );
        } else {
            $reactionType = $reactionTypes[$reaction];
            $reactionModel->fireEvent("BeforeGet", [
                "RecordType" => [
                    "Discussion" => "Discussion-Total",
                    "Comment" => "Comment-Total",
                ],
                "ApplyRestrictions" => true,
            ]);
            $data = $reactionModel->getRecordsWhere(
                [
                    "TagID" => $reactionType["TagID"],
                    "RecordType" => ["Discussion-Total", "Comment-Total"],
                    "Total >=" => 1,
                ],
                "UserTag.DateInserted",
                "desc",
                $limit + 1,
                $offset
            );
        }

        $sender->setData("_CurrentRecords", $reactionModel->LastCount);
        if (count($data) > $limit) {
            array_pop($data);
        }
        $sender->setData("Data", $data);

        // Set up head
        $sender->Head = new HeadModule($sender);

        $sender->addJsFile("jquery.js");
        $sender->addJsFile("jquery.livequery.js");
        $sender->addJsFile("global.js");
        $sender->addJsFile("jquery.form.js");
        $sender->addJsFile("jquery.popup.js");

        // A little ugly bit will do the trick until this tiled layout has been rewritten.
        // This janky jquery masonry plugin is not peformant and is very buggy.
        // Until it is re-implemented, data-driven (new) themes will default to use the "list" layout
        // unless they explicitly opt-in to using tiles (can be configured in the Reactions plugin settings).
        //
        // See https://github.com/vanilla/support/issues/4368#issuecomment-920959129
        if (\Gdn::themeFeatures()->useDataDrivenTheme()) {
            \Gdn::config()->touch("Vanilla.Reactions.BestOfStyle", "List", false);
        }

        if (Gdn::config("Vanilla.Reactions.BestOfStyle", "Tiles") == "Tiles") {
            $sender->addJsFile("library/jQuery-Masonry/jquery.masonry.js", "vanilla"); // I customized this to get proper callbacks.
            $sender->addJsFile("library/jQuery-InfiniteScroll/jquery.infinitescroll.min.js", "vanilla");
            $sender->addJsFile("tile.js", "vanilla");
            $sender->CssClass .= " NoPanel";
            $view = $sender->deliveryType() == DELIVERY_TYPE_VIEW ? "tile_items" : "tiles";
        } else {
            $view = "BestOf";
            $sender->addModule("GuestModule");
            $sender->addModule("SignedInModule");
            $sender->addModule("BestOfFilterModule");
        }

        $sender->addCssFile("style.css");
        $sender->addCssFile("vanillicon.css", "static");

        // Set the title, breadcrumbs, canonical
        $sender->title(t("Best Of"));
        $sender->setData("Breadcrumbs", [["Name" => t("Best Of"), "Url" => "/bestof/everything"]]);

        // set canonical url
        if ($sender->Data["isHomepage"]) {
            $sender->canonicalUrl(url("/", true));
        } else {
            $sender->canonicalUrl(
                url(
                    concatSep(
                        "/",
                        "bestof/" . $reaction,
                        pageNumber($offset, $limit, true, Gdn::session()->UserID != 0)
                    ),
                    true
                ),
                Gdn::session()->UserID == 0
            );
        }

        // Render the page (or deliver the view)
        $sender->render($view, "reactions", "dashboard");
    }

    /**
     * @param $Sender
     * @param $Args
     * @throws Exception
     */
    public function base_afterUserInfo_handler($Sender, $Args)
    {
        // Fetch the view helper functions.
        include_once Gdn::controller()->fetchViewLocation("reaction_functions", "reactions", "dashboard");

        $reactionsModuleEnabled = \Gdn::themeFeatures()->get("NewReactionsModule");
        if ($reactionsModuleEnabled) {
            /** @var ReactionsModule $reactionModule */
            $reactionModule = Gdn::getContainer()->get(ReactionsModule::class);
            echo $reactionModule;
        } else {
            $this->displayProfileCounts();
        }
    }

    /**
     * Display legacy profile Counts.
     *
     * @see writeProfileCounts()
     */
    private function displayProfileCounts(): void
    {
        $heading = '<h2 class="H">' . t("Reactions") . "</h2>";
        if (BoxThemeShim::isActive()) {
            BoxThemeShim::startWidget();
            BoxThemeShim::startHeading();
            echo $heading;
            BoxThemeShim::endHeading();
            BoxThemeShim::startBox("ReactionsWrap");
            writeProfileCounts();
            BoxThemeShim::endBox();
            BoxThemeShim::endWidget();
        } else {
            echo '<div class="ReactionsWrap">';
            echo $heading;
            writeProfileCounts();
            echo "</div>";
        }
    }
}
