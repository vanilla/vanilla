<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Gdn_UserException;
use Symfony\Component\Console\Input\InputOption;
use Vanilla\Cli\Utils\DatabaseCommand;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Validate the data of a Vanilla database.
 */
class ValidateDataCommand extends DatabaseCommand
{
    use ScriptLoggerTrait;
    private array $errors = [];
    private int $success = 0;
    private string $limit = "";

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName("validate-data")->setDescription("Command to validate data.");

        $definition = $this->getDefinition();
        $definition->addOption(
            new InputOption(
                "limit",
                "l",
                InputOption::VALUE_OPTIONAL,
                "Max number of records to return when validating the data."
            )
        );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        if ($input->getOption("limit")) {
            $this->limit = " LIMIT " . $input->getOption("limit");
        }
    }

    /**
     * Run some validations on the DB to test its internal integrity.
     *
     * @throws Gdn_UserException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errors = 0;
        $warnings = 0;

        // Users
        $this->validateUsers();
        $this->validatePoints();
        $this->validateRoles();
        $this->validateUserMeta();

        // Forum
        $this->validateCategories();
        $this->validateDiscussions();
        $this->validateComment();
        $this->validateMedia();
        $this->validateTags();
        $this->validateReactions();
        $this->validateConversation();

        // Plugin specific
        $this->validateBadges();
        $this->validateGroups();
        $this->validateEvents();
        $this->validateIdeation();
        $this->validateKB();

        if (!empty($this->errors)) {
            $this->logger()->title("Errors log");
        }

        foreach ($this->errors as $error) {
            $message = $error["message"];
            $level = $error["level"];
            $this->logger()->$level($message);

            if ($level == "error") {
                $errors++;
            } elseif ($level == "warning") {
                $warnings++;
            }
        }

        $this->logger()->title("Summary");
        $this->logger()->info(
            "Success:" . $this->success . PHP_EOL . "Errors: $errors" . PHP_EOL . "Warnings: $warnings"
        );
        return self::SUCCESS;
    }

    /**
     * Validate GDN_User
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateUsers(): void
    {
        $this->logger()->title("Validating users");

        // Dates
        $this->assertValidDate("GDN_User", "DateInserted");
        $this->assertValidDate("GDN_User", "DateUpdated");
        $this->assertValidDate("GDN_User", "DateLastActive", where: "Admin < 2");
        $this->assertValidDate("GDN_User", "DateFirstVisit", where: "Admin < 2");

        // Not NULL
        $this->assertNotNULL("GDN_User", "DateLastActive", where: "Admin < 2");
        $this->assertNotNULLOrEmpty(
            "GDN_User",
            "HashMethod",
            "HashMethod must be set. Use 'Reset' if you don't know the hash method."
        );
        $this->assertNotNULLOrEmpty("GDN_User", "Password");

        // Duplicates
        $this->assertNoDuplicates("GDN_User", ["Name"]);
        $this->assertNoDuplicates("GDN_User", ["Email"]);
        $this->assertNoDuplicates(
            "GDN_User",
            ["Photo"],
            "Duplicated avatars might be deleted. Please make sure the users all have unique avatar paths."
        );

        // Legacy Field
        $this->assertFieldIsNULL("GDN_User", "Title");
        $this->assertFieldIsNULL("GDN_User", "Location");
        $this->assertFieldIsNULL("GDN_User", "About");
        $this->assertFieldIsNULL("GDN_User", "DateOfBirth");

        // Super-admin users
        $this->assertNoRecords(
            "select * from GDN_User where Name = 'System'",
            "Please make sure the System user is not an actual user.",
            "warning"
        );

        $this->assertNoRecords(
            "select * from GDN_User where Name = 'StopForumSpam'",
            "Please make sure the StopForumSpam user is not an actual user.",
            "warning"
        );

        $this->assertNoRecords(
            "select * from GDN_User where Name = 'Akismet'",
            "Please make sure the Akismet user is not an actual user.",
            "warning"
        );

        $this->assertCounts(
            "GDN_User",
            "UserID",
            "CountDiscussions",
            "select InsertUserID as UserID, count(*) cou from GDN_Discussion group by InsertUserID"
        );

        $this->assertCounts(
            "GDN_User",
            "UserID",
            "CountComments",
            "select InsertUserID as UserID, count(*) cou from GDN_Comment group by InsertUserID"
        );

        $this->assertNoRecords(
            "Select * from GDN_User where Name like '% %'",
            "users with a space in their name. Make sure to their site config with `Garden.User.ValidationRegex` = \"\\p{N}\\p{L}\\p{M}\\p{Pc}\"",
            "warning"
        );
    }

    /**
     * Validate GDN_UserPoints.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validatePoints(): void
    {
        $this->logger()->title("Validating points");
        $this->assertUserExists("GDN_UserPoints", "UserID");
        $this->assertNoRecords(
            "SELECT
            *
            FROM
            GDN_User u
            JOIN GDN_UserPoints up ON up.SlotType = 'a'
    AND up.TimeSlot = '1970-01-01 00:00:00'
    AND up.Source = 'Total'
    AND CategoryID = 0
    AND up.UserID = u.UserID
        where up.Points <> u.Points",
            "users with a mismatch of their points between GDN_User and GDN_UserPoints."
        );

        $this->assertNoRecords(
            "SELECT
            *
            FROM
            GDN_User u
            JOIN GDN_UserPoints up ON up.SlotType = 'a'
    AND up.TimeSlot = '1970-01-01 00:00:00'
    AND up.Source = 'Total'
    AND CategoryID = 0
    AND up.UserID = u.UserID
        where u.Points > 0 and up.Points is NULL",
            "users with points in GDN_User but that are missing a record in GDN_UserPoints."
        );
    }

    /**
     * Validate the GDN_Role and GDN_UserRole tables.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateRoles(): void
    {
        $this->logger()->title("Validating roles");
        $this->assertRecordWithValueExists(
            "GDN_Role",
            "Type",
            "guest",
            "There is no guest role. Make sure at least one of the roles has the type 'guest'."
        );

        $this->assertNoRecords(
            "select * from GDN_UserRole where RoleID not in (select RoleID from GDN_Role)",
            "user roles that are assigned to an invalid role."
        );

        $this->assertNoRecords(
            "select * from GDN_User where UserID not in (select UserID from GDN_UserRole) and Deleted = 0 and Admin = 0",
            "users no not have a role assigned to them. Please go to `/dba/fixuserrole` to fix this."
        );
    }

    /**
     * Validate the GDN_UserMeta table.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateUserMeta(): void
    {
        $this->logger()->title("Validating user meta");
        $this->assertNotNULLOrEmpty("GDN_UserMeta", "Value");
        $this->assertNotNULLOrEmpty("GDN_UserMeta", "QueryValue");
        $this->assertNoDuplicates("GDN_UserMeta", ["Name", "UserID"]);
        $this->assertNoRecords(
            "with userProfile as (
select * from GDN_UserMeta
where Name like 'Profile.%'
)
select * from userProfile where UserMetaID not in (
select UserMetaID from userProfile up
join GDN_profileField pf on concat('Profile.' ,pf.apiName) = up.Name)",
            "user meta records with an invalid profile field."
        );

        $this->assertNoRecords(
            "select UserID from GDN_UserMeta where Name = 'Plugin.Signatures.Sig'
and UserID not in (select UserID from GDN_UserMeta where Name = 'Plugin.Signatures.Format')",
            "user signature that are missing a matching 'Plugin.Signatures.Format' record."
        );
    }

    /**
     * Validate the GDN_Category table.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateCategories(): void
    {
        $this->logger()->title("Validating categories");

        // Dates
        $this->assertValidDate("GDN_Category", "DateInserted");
        $this->assertValidDate("GDN_Category", "DateUpdated");

        // Not NULL
        $this->assertNotNULLOrEmpty("GDN_Category", "Name");
        $this->assertNotNULLOrEmpty("GDN_Category", "UrlCode", where: "CategoryID > 0");

        // Duplicates
        $this->assertNoDuplicates("GDN_Category", ["Name"], level: "warning");
        $this->assertNoDuplicates("GDN_Category", ["UrlCode"]);

        // Tree structure
        $this->getDatabase()->query(
            "select CategoryID from GDN_Category where ParentCategoryID not in (Select CategoryID from GDN_Category)"
        );

        // Category count
        $categoryCount = $this->getCountWhere("GDN_Category");
        if ($categoryCount > 200) {
            $this->logger()->error(
                "There are $categoryCount categories. Having more than 200 categories can severely affect performances."
            );
        } else {
            echo ".";
            $this->success++;
        }

        $this->assertNoRecords(
            "select * from GDN_Category where UrlCode like '%/%'",
            " records in `GDN_Category`.`UrlCode` with an `/`. This symbol is invalid."
        );
        $this->assertNoRecords(
            "Select * from GDN_Category where CountAllDiscussions = 0",
            "categories without any content.",
            "warning"
        );

        $this->assertCounts(
            "GDN_Category",
            "CategoryID",
            "CountDiscussions",
            "select CategoryID, count(*) cou from GDN_Discussion group by CategoryID"
        );

        $this->assertParentExists("GDN_Category", "ParentCategoryID", "GDN_Category", "CategoryID", "CategoryID > 0");
    }

    /**
     * Validate the GDN_Discussion table.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateDiscussions(): void
    {
        $this->logger()->title("Validating discussions");

        // Dates
        $this->assertValidDate("GDN_Discussion", "DateInserted");
        $this->assertValidDate("GDN_Discussion", "DateUpdated");
        $this->assertValidDate("GDN_Discussion", "DateLastComment");

        // Not NULL
        $this->assertNotNULLOrEmpty(
            "GDN_Discussion",
            "Name",
            "There are empty discussion names. Those will show as `(Untitled)`",
            level: "warning"
        );
        $this->assertNotNULLOrEmpty("GDN_Discussion", "Body", level: "warning");
        $this->assertNotNULLOrEmpty("GDN_Discussion", "Format");

        $this->assertParentExists("GDN_Discussion", "CategoryID", "GDN_Category", "CategoryID");
        $this->assertNoRecords(
            "select * from GDN_Discussion where Body like '%Base64%'",
            "discussions with a base64 encoded images."
        );

        // Author
        $this->assertUserExists("GDN_Discussion", "InsertUserID", level: "warning");
        $this->assertUserExists("GDN_Discussion", "UpdateUserID", level: "warning");

        $this->assertCounts(
            "GDN_Discussion",
            "DiscussionID",
            "CountComments",
            "select DiscussionID, count(*) cou from GDN_Comment group by DiscussionID"
        );
    }

    /**
     * Validate the GDN_Comment table.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateComment(): void
    {
        $this->logger()->title("Validating comments");

        // Dates
        $this->assertValidDate("GDN_Comment", "DateInserted");
        $this->assertValidDate("GDN_Comment", "DateUpdated");

        // Not NULL
        $this->assertNotNULLOrEmpty("GDN_Comment", "Body", level: "warning");
        $this->assertNotNULLOrEmpty("GDN_Comment", "Format");
        $this->assertNotNULLOrEmpty("GDN_Comment", "parentRecordType");
        $this->assertNotNULLOrEmpty("GDN_Comment", "parentRecordID");

        // Discussion
        $this->assertParentExists("GDN_Comment", "DiscussionID", "GDN_Discussion", "DiscussionID");

        $this->assertNoRecords(
            "select * from GDN_Comment where Body like '%Base64%'",
            "comments with a base64 encoded images."
        );

        // User
        $this->assertUserExists("GDN_Comment", "InsertUserID", level: "warning");
        $this->assertUserExists("GDN_Comment", "UpdateUserID", level: "warning");

        // Parent Comment
        $this->assertNoRecords(
            "Select * from GDN_Comment where ParentCommentID not in (select CommentID from GDN_Comment)",
            "comments with an invalid parent comment."
        );
        $this->assertNoRecords(
            "Select * from GDN_Comment where ParentCommentID = CommentID",
            "comments with a parent comment that is the same as the comment."
        );

        // Parent RecordType
        $this->assertNoRecords(
            "Select * from GDN_Comment where DiscussionID <> parentRecordID",
            " comments with an invalid parentRecordID."
        );

        // Attributes
        $this->assertNoRecords(
            "Select * from GDN_Comment where Attributes = ''",
            " comment attributes that are empty."
        );
    }

    /**
     * Validate the GDN_Media table.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateMedia(): void
    {
        $this->logger()->title("Validating media");
        $this->assertUserExists("GDN_Media", "InsertUserID");
        $this->assertValidDate("GDN_Media", "DateInserted");
        $this->assertParentExists(
            "GDN_Media",
            "ForeignID",
            "GDN_Discussion",
            "DiscussionID",
            "ForeignTable = 'Discussion'"
        );
        $this->assertParentExists("GDN_Media", "ForeignID", "GDN_Comment", "CommentID", "ForeignTable = 'Comment'");
        $this->assertParentExists("GDN_Media", "ForeignID", "GDN_User", "UserID", "ForeignTable = 'embed'");
    }

    /**
     * Validate the GDN_Tag and GDN_TagDiscussion tables.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateTags(): void
    {
        $this->logger()->title("Validating tags");

        // Tag
        $this->assertValidDate("GDN_Tag", "DateInserted");
        $this->assertNoDuplicates("GDN_Tag", ["Name"]);

        $this->assertNoRecords(
            "with td as (select TagID, count(*) cou from GDN_TagDiscussion td group by TagID)
                    select *
                    from GDN_Tag t
                    left join td on td.TagID = t.TagID
                    where ifnull(cou,0) <> td.cou
                    ",
            "tags where the CountDiscussions field do not match the GDN_TagDiscussion table."
        );

        // TagDiscussion
        $this->assertParentExists("GDN_TagDiscussion", "TagID", "GDN_Tag", "TagID");
        $this->assertParentExists("GDN_TagDiscussion", "DiscussionID", "GDN_Discussion", "DiscussionID");
    }

    /**
     * Validate the GDN_UserTag table.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateReactions(): void
    {
        if ($this->getCountWhere("GDN_Tag", "Type = 'Reaction'") === 0) {
            $this->logger()->info("Skipping validating reactions because there are no reactions in GDN_Tag.");
            return;
        }
        $this->logger()->title("Validating reactions");

        $this->assertUserExists("GDN_UserTag", "UserID", where: "RecordType <> 'User'");
        $this->assertParentExists("GDN_UserTag", "TagID", "GDN_Tag", "TagID");
        $this->assertParentExists(
            "GDN_UserTag",
            "RecordID",
            "GDN_Discussion",
            "DiscussionID",
            "RecordType = 'discussion'"
        );
        $this->assertParentExists("GDN_UserTag", "RecordID", "GDN_Comment", "CommentID", "RecordType = 'comment'");

        $this->assertNoRecords(
            "
            select distinct ut.RecordID from GDN_UserTag ut
            left join GDN_UserTag up on ut.RecordID = up.RecordID and up.RecordType like 'Discussion-total'
            where ut.RecordType = 'Discussion' and up.RecordID is NULL;",
            "discussion reactions are missing a total reaction record. Go to `/utility/recalculateReactions to fix this."
        );

        $this->assertNoRecords(
            "
            select distinct ut.RecordID from GDN_UserTag ut
            left join GDN_UserTag up on ut.RecordID = up.RecordID and up.RecordType like 'Comment-total'
            where ut.RecordType = 'Comment' and up.RecordID is NULL;",
            "discussion reactions are missing a total reaction record. Go to `/utility/recalculateReactions to fix this."
        );
    }

    /**
     * Validate GDN_Conversation, GDN_ConversationMessage, and GDN_UserConversation.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateConversation(): void
    {
        $this->logger()->title(PHP_EOL . "Validating conversations");

        // Conversation
        $this->assertValidDate("GDN_Conversation", "DateInserted");
        $this->assertValidDate("GDN_Conversation", "DateUpdated");
        $this->assertUserExists("GDN_Conversation", "InsertUserID");
        $this->assertUserExists("GDN_Conversation", "UpdateUserID");

        // Message
        $this->assertValidDate("GDN_ConversationMessage", "DateInserted");
        $this->assertUserExists("GDN_ConversationMessage", "InsertUserID");
        $this->assertNotNULLOrEmpty("GDN_ConversationMessage", "Body");
        $this->assertNotNULLOrEmpty("GDN_ConversationMessage", "Format");
        $this->assertParentExists("GDN_ConversationMessage", "ConversationID", "GDN_Conversation", "ConversationID");

        // UserConversation
        $this->assertUserExists("GDN_UserConversation", "UserID");
        $this->assertParentExists("GDN_UserConversation", "ConversationID", "GDN_Conversation", "ConversationID");
    }

    /**
     * Validate GDN_Badge, and GDN_UserBadge.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateBadges(): void
    {
        if (!$this->tableExists("GDN_Badge")) {
            $this->logger()->info("Skipping validating badges because the GDN_Badge table is missing.");
            return;
        }

        $this->logger()->title("Validating badges");

        // Badge
        $this->assertNotNULLOrEmpty(
            "GDN_Badge",
            "Class",
            "The Class field is NULL or Empty.  This will prevent the badges from being automatically awarded.",
            "warning"
        );
        $this->assertNotNULLOrEmpty(
            "GDN_Badge",
            "Level",
            "The level field is NULL or Empty. This will prevent the badges from being automatically awarded.",
            "warning"
        );
        $this->assertNotNULLOrEmpty(
            "GDN_Badge",
            "Attributes",
            "The `Attributes` field is NULL or Empty. This will prevent the badges from being automatically awarded.",
            "warning"
        );

        // UserBadge
        $this->assertNotNULL("GDN_UserBadge", "DateCompleted");
        $this->assertUserExists("GDN_UserBadge", "UserID");
        $this->assertParentExists("GDN_UserBadge", "BadgeID", "GDN_Badge", "BadgeID");

        $this->assertNoRecords(
            "Select * from GDN_UserBadge where Status <> 'given'",
            "badges in GDN_UserBadge that do not have the `given` status. This field is required for the badge to be awarded."
        );

        // Counts
        $this->assertCounts(
            "GDN_User",
            "UserID",
            "CountBadges",
            "select UserID, count(*) cou from GDN_UserBadge  where Status = 'given' group by UserID"
        );

        $this->assertCounts(
            "GDN_Badge",
            "BadgeID",
            "CountRecipients",
            "select BadgeID, count(*) cou from GDN_UserBadge where Status = 'given' group by BadgeID"
        );
    }

    /**
     * Validate the GDN_Group and GDN_UserGroup.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateGroups(): void
    {
        if (!$this->tableExists("GDN_Group")) {
            $this->logger()->info("Skipping validating group because the GDN_Group table is missing.");
            return;
        }

        $this->logger()->title("Validating groups");

        // Group
        $this->assertRecordWithValueExists("GDN_Category", "AllowGroups", 1);
        $this->assertNotNULL("GDN_Group", "InsertUserID");
        $this->assertUserExists("GDN_Group", "InsertUserID");

        $this->assertNoRecords(
            "SELECT
                        *
                    FROM
                        GDN_Discussion d
                        JOIN GDN_Category c ON c.CategoryID = d.CategoryID
                    WHERE
                        d.GroupID IS NOT NULL
                        AND c.AllowGroups = 0",
            "discussions that are part of a group but the category does not allow groups."
        );

        // UserGroup
        $this->assertUserExists("GDN_UserGroup", "UserID");
        $this->assertParentExists("GDN_UserGroup", "GroupID", "GDN_Group", "GroupID");
        $this->assertNoRecords(
            "with
            usergroup as (
            select distinct GroupID
            from GDN_UserGroup
            where Role = 'leader'

            )
        select
            *
        from GDN_Group g
        left join usergroup ug on ug.GroupID = g.GroupID
        where ug.GroupID is null",
            "groups that do not have a leader."
        );
    }

    /**
     * Validate the GDN_Event and GDN_UserEvent tables.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateEvents(): void
    {
        if (!$this->tableExists("GDN_Event")) {
            $this->logger()->info("Skipping validating events because the GDN_Event table is missing.");
            return;
        }

        $this->logger()->title("Validating events");

        // Event
        $this->assertNotNULL("GDN_Event", "DateStarts");
        $this->assertNotNULLOrEmpty("GDN_Event", "Description");
        $this->assertUserExists("GDN_Event", "InsertUserID");

        // UserEvent
        $this->assertUserExists("GDN_UserEvent", "UserID");
        $this->assertParentExists("GDN_UserEvent", "EventID", "GDN_Event", "EventID");
    }

    /**
     * Validate GDN_KnowledgeBase, GDN_KnowledgeCategory, GDN_Article, and GDN_ArticleRevision.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateKB(): void
    {
        if (!$this->tableExists("GDN_knowledgeBase")) {
            $this->logger()->info("Skipping validating Knowledge Base because the GDN_knowledgeBase table is missing.");
            return;
        }
        $this->logger()->title("Validating knowledge base");

        // KnowledgeBase
        $this->assertNotNULLOrEmpty("GDN_knowledgeBase", "description");
        $this->assertNoRecords(
            "select * from GDN_knowledgeBase where urlCode like '%/%'",
            " records in `GDN_knowledgeBase`.`urlCode` with an `/`. This symbol is invalid."
        );
        $this->assertNoRecords(
            "select * from GDN_knowledgeBase where urlCode like '%\_%'",
            " records in `GDN_knowledgeBase`.`urlCode` with an `_`. This symbol is invalid."
        );
        $this->assertNoRecords(
            "select * from GDN_knowledgeBase where length(description) > 90",
            "knowledge base descriptions must not exceed 90 chars."
        );

        $this->assertNoDuplicates("GDN_knowledgeBase", ["urlCode"]);
        $this->assertNoRecords(
            "select * from GDN_knowledgeBase where knowledgeBaseID not in (select knowledgeBaseID from GDN_knowledgeCategory where parentID = -1)",
            "knowledge bases without a root knowledgeCategory."
        );

        // Knowledge Category
        $this->assertValidDate("GDN_knowledgeCategory", "dateInserted");
        $this->assertValidDate("GDN_knowledgeCategory", "dateUpdated");
        $this->assertUserExists("GDN_knowledgeCategory", "insertUserID");
        $this->assertParentExists(
            "GDN_knowledgeCategory",
            "parentID",
            "GDN_knowledgeCategory",
            "knowledgeCategoryID",
            "parentID > 0"
        );
        $this->assertParentExists("GDN_knowledgeCategory", "knowledgeBaseID", "GDN_knowledgeBase", "knowledgeBaseID");

        // article
        $this->assertValidDate("GDN_article", "dateInserted");
        $this->assertValidDate("GDN_article", "dateUpdated");
        $this->assertUserExists("GDN_article", "insertUserID");
        $this->assertUserExists("GDN_article", "updateUserID");
        $this->assertParentExists("GDN_article", "knowledgeCategoryID", "GDN_knowledgeCategory", "knowledgeCategoryID");
        $this->assertNotNULLOrEmpty("GDN_article", "status");

        // article revisions
        $this->assertValidDate("GDN_articleRevision", "dateInserted");
        $this->assertUserExists("GDN_articleRevision", "insertUserID");
        $this->assertParentExists("GDN_articleRevision", "articleID", "GDN_article", "articleID");
        $this->assertNotNULLOrEmpty("GDN_articleRevision", "body");
        $this->assertNotNULLOrEmpty("GDN_articleRevision", "bodyRendered");
        $this->assertNotNULLOrEmpty(
            "GDN_articleRevision",
            "status",
            "This field needs to be set to `published` for the article to show.",
            level: "warning"
        );
    }

    /**
     * Validate the ideation.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private function validateIdeation(): void
    {
        if (!$this->tableExists($this->columnExists("GDN_Category", "IdeationType"))) {
            $this->logger()->info(
                "Skipping validating ideation because the GDN_Category table is missing the `IdeationType` column."
            );
            return;
        }

        $this->logger()->title(PHP_EOL . "Validating Ideas");
        $this->assertNoRecords(
            "select * from GDN_Discussion where Type = 'Idea' and concat('d-', DiscussionID) not in (select ForeignID from GDN_Attachment where Type = 'status')",
            "ideas discussion records without a matching record in GDN_Attachment."
        );

        $this->assertNoRecords(
            "select * from GDN_Discussion where Type = 'Idea' and Score = 0 and DiscussionID in (select RecordID from GDN_UserTag where RecordType = 'Discussion-Total' and TagID = 1)",
            "ideas discussions with a score of 0. This field needs to be set to show the votes properly."
        );

        $this->assertNoRecords(
            "select * from GDN_Discussion where Type = 'Idea' and CategoryID not in (select CategoryID from GDN_Category where AllowedDiscussionTypes like '%Idea%')",
            "ideas discussions that are not in a category that allows ideas.",
            "warning"
        );
    }

    // UTILS

    /**
     * Validate that none of the field have a date of `0000-00-00 00:00:00`.
     *
     * @param string $table
     * @param string $field
     * @param string|null $message
     * @param string $level
     * @param string|null $where
     * @return void
     * @throws Gdn_UserException
     */
    private function assertValidDate(
        string $table,
        string $field,
        ?string $message = null,
        string $level = "error",
        ?string $where = ""
    ): void {
        $message = $message ?? "Field $field in table $table has invalid dates";
        $whereQuery = "";

        if ($where) {
            $whereQuery = "and $where";
        }

        $result = $this->getDatabase()
            ->query("select * from $table where $field = 0 $whereQuery limit 1")
            ->count();
        if ($result && $result > 0) {
            $this->errors[] = [
                "message" => $message,
                "level" => $level,
            ];
            echo "E";
        } else {
            echo ".";
            $this->success++;
        }
    }

    /**
     * Make sure a field is NULL.
     *
     * @param string $table
     * @param string $field
     * @param string|null $message
     * @param string $level
     * @return void
     * @throws Gdn_UserException
     */
    private function assertFieldIsNULL(
        string $table,
        string $field,
        ?string $message = null,
        string $level = "error"
    ): void {
        $message = $message ?? "Field $field in table $table shouldn't be used.";
        $result = $this->getDatabase()
            ->query("select * from $table where $field is not NULL limit 1")
            ->count();

        if ($result) {
            $this->errors[] = [
                "message" => $message,
                "level" => $level,
            ];
            echo "E";
        } else {
            echo ".";
            $this->success++;
        }
    }

    /**
     * Make sure a field is not NULL or Empty.
     *
     * @param string $table
     * @param string $field
     * @param string|null $message
     * @param string $level
     * @param string|null $where
     * @return bool
     * @throws Gdn_UserException
     */
    private function assertNotNULLOrEmpty(
        string $table,
        string $field,
        ?string $message = null,
        string $level = "error",
        ?string $where = null
    ): bool {
        $message = $message ?? "Field $field in table $table has NULL or empty values";

        $queryWhere = "";
        if (isset($where)) {
            $queryWhere = "and $where";
        }

        $result = $this->getDatabase()
            ->query(
                "select * from $table where $field is NULL or $field = '' or $field = '<body></body>' $queryWhere limit 1"
            )
            ->count();

        if ($result) {
            $this->errors[] = [
                "message" => $message,
                "level" => $level,
            ];
            echo "E";
        } else {
            echo ".";
            $this->success++;
        }
        return !$result;
    }

    /**
     * Make sure a field is not NULL.
     *
     * @param string $table
     * @param string $field
     * @param string|null $message
     * @param string $level
     * @param string|null $where
     * @return void
     * @throws Gdn_UserException
     */
    private function assertNotNULL(
        string $table,
        string $field,
        ?string $message = null,
        string $level = "error",
        ?string $where = null
    ): void {
        $message = $message ?? "Field $field in table $table has NULL or empty values";
        $whereQuery = "";
        if ($where) {
            $whereQuery = "and $where";
        }

        $result = $this->getDatabase()
            ->query("select * from $table where $field is NULL $whereQuery limit 1")
            ->count();

        if ($result) {
            $this->errors[] = [
                "message" => $message,
                "level" => $level,
            ];
            echo "E";
        } else {
            echo ".";
            $this->success++;
        }
    }

    /**
     * Make sure there are no duplicates.
     *
     * @param string $table
     * @param array $fields
     * @param string|null $message
     * @param string $level
     * @return void
     * @throws Gdn_UserException
     */
    private function assertNoDuplicates(
        string $table,
        array $fields,
        ?string $message = null,
        string $level = "error"
    ): void {
        $queryField = implode(", ", $fields);
        $message = $message ?? "Field $queryField in table $table has duplicates";
        $result = $this->getDatabase()
            ->query("select $queryField from $table group by $queryField having count(*) > 1 $this->limit")
            ->count();
        if ($result) {
            $this->errors[] = [
                "message" => $message,
                "level" => $level,
            ];
            echo "E";
        } else {
            echo ".";
            $this->success++;
        }
    }

    /**
     * Make sure at least one records has a specific value.
     *
     * @param string $table
     * @param string $field
     * @param string $value
     * @param string|null $message
     * @param string $level
     * @return bool
     * @throws Gdn_UserException
     */
    private function assertRecordWithValueExists(
        string $table,
        string $field,
        string $value,
        ?string $message = null,
        string $level = "error"
    ): bool {
        $message = $message ?? "Record with $field = $value in table $table exist";
        $result = $this->getDatabase()
            ->query("select * from $table where $field = '$value' limit 1")
            ->count();
        if ($result == 0) {
            $this->errors[] = [
                "message" => $message,
                "level" => $level,
            ];
            echo "E";
        } else {
            echo ".";
            $this->success++;
        }

        return $result;
    }

    /**
     * Assert that a user is part of the GDN_User table.
     *
     * @param string $table
     * @param string $field
     * @param string $level
     * @param string|null $where
     * @return void
     * @throws Gdn_UserException
     */
    private function assertUserExists(
        string $table,
        string $field,
        string $level = "error",
        ?string $where = null
    ): void {
        $queryWhere = "";
        if ($where) {
            $queryWhere = "and $where";
        }

        $insertUserCount = $this->getCountWhere(
            $table,
            "$field not in (select UserID from GDN_User) $queryWhere $this->limit"
        );
        if ($insertUserCount > 0) {
            $this->errors[] = [
                "message" => "There are $insertUserCount records in `$table`.`$field`referring to a user that doesn't exists.",
                "level" => $level,
            ];
            echo "E";
        } else {
            echo ".";
            $this->success++;
        }
    }

    /**
     * Make sure the parent record exists.
     *
     * @param string $table
     * @param string $field
     * @param string $parentTable
     * @param string $parentField
     * @param string|null $where
     * @param string $level
     * @return void
     * @throws Gdn_UserException
     */
    private function assertParentExists(
        string $table,
        string $field,
        string $parentTable,
        string $parentField,
        ?string $where = null,
        string $level = "error"
    ): void {
        $whereQuery = "";
        if ($where) {
            $whereQuery = "and $where";
        }

        $recordCount = $this->getDatabase()
            ->query(
                "select * from $table t where $field not in (select $parentField from $parentTable) $whereQuery $this->limit"
            )
            ->count();
        if ($recordCount > 0) {
            $this->errors[] = [
                "message" => "There are $recordCount records in `$table`.`$field` that do not match any record from `$parentTable`.`$parentField`.",
                "level" => $level,
            ];
            echo "E";
        } else {
            echo ".";
            $this->success++;
        }
    }

    /**
     * Make sure no records matches the query.
     *
     * @param string $query
     * @param string $message
     * @param string $level
     * @return void
     * @throws Gdn_UserException
     */
    private function assertNoRecords(string $query, string $message, string $level = "error"): void
    {
        $query .= " $this->limit";
        $recordCount = $this->getDatabase()
            ->query($query)
            ->count();
        if ($recordCount > 0) {
            $this->errors[] = [
                "message" => "There are $recordCount " . $message,
                "level" => $level,
            ];
            echo "E";
        } else {
            echo ".";
            $this->success++;
        }
    }

    /**
     * Validate that the count of a field matches the one generated by a query.
     *
     * @param string $table
     * @param string $id
     * @param string $field
     * @param string $groupQuery a query that uses the id and the count of the records. The counts must be aliased as `cou`.
     * @param string $level
     * @return void
     * @throws Gdn_UserException
     */
    private function assertCounts(
        string $table,
        string $id,
        string $field,
        string $groupQuery,
        string $level = "error"
    ): void {
        $recordCount = $this->getDatabase()
            ->query(
                "select $field from $table
        join ($groupQuery) as t on t.$id = $table.$id
        where t.cou <> $field
        $this->limit
        "
            )
            ->count();

        if ($recordCount > 0) {
            $this->errors[] = [
                "message" => "There are $recordCount records in `$table`.`$field` with mismatched count.",
                "level" => $level,
            ];
            echo "E";
        } else {
            echo ".";
            $this->success++;
        }
    }
}
