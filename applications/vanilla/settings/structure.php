<?php if (!defined('APPLICATION')) {
    exit();
      }
/**
 * Vanilla database structure.
 *
 * Called by VanillaHooks::Setup() to update database upon enabling app.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @since 2.0
 * @package Vanilla
 */

if (!isset($Drop)) {
    $Drop = false;
}

if (!isset($Explicit)) {
    $Explicit = true;
}

$SQL = Gdn::Database()->SQL();
$Construct = Gdn::Database()->Structure();
$Px = $Construct->DatabasePrefix();

$Construct->Table('Category');
$CategoryExists = $Construct->TableExists();
$PermissionCategoryIDExists = $Construct->ColumnExists('PermissionCategoryID');

$LastDiscussionIDExists = $Construct->ColumnExists('LastDiscussionID');

$Construct->PrimaryKey('CategoryID')
    ->Column('ParentCategoryID', 'int', true)
    ->Column('TreeLeft', 'int', true)
    ->Column('TreeRight', 'int', true)
    ->Column('Depth', 'int', true)
    ->Column('CountDiscussions', 'int', '0')
    ->Column('CountComments', 'int', '0')
    ->Column('DateMarkedRead', 'datetime', null)
    ->Column('AllowDiscussions', 'tinyint', '1')
    ->Column('Archived', 'tinyint(1)', '0')
    ->Column('Name', 'varchar(255)')
    ->Column('UrlCode', 'varchar(255)', true)
    ->Column('Description', 'varchar(500)', true)
    ->Column('Sort', 'int', true)
    ->Column('CssClass', 'varchar(50)', true)
    ->Column('Photo', 'varchar(255)', true)
    ->Column('PermissionCategoryID', 'int', '-1')// default to root.
    ->Column('PointsCategoryID', 'int', '0')// default to global.
    ->Column('HideAllDiscussions', 'tinyint(1)', '0')
    ->Column('DisplayAs', array('Categories', 'Discussions', 'Heading', 'Default'), 'Default')
    ->Column('InsertUserID', 'int', false, 'key')
    ->Column('UpdateUserID', 'int', true)
    ->Column('DateInserted', 'datetime')
    ->Column('DateUpdated', 'datetime')
    ->Column('LastCommentID', 'int', null)
    ->Column('LastDiscussionID', 'int', null)
    ->Column('LastDateInserted', 'datetime', null)
    ->Column('AllowedDiscussionTypes', 'varchar(255)', null)
    ->Column('DefaultDiscussionType', 'varchar(10)', null)
    ->Set($Explicit, $Drop);

$RootCategoryInserted = false;
if ($SQL->GetWhere('Category', array('CategoryID' => -1))->NumRows() == 0) {
    $SQL->Insert('Category', array('CategoryID' => -1, 'TreeLeft' => 1, 'TreeRight' => 4, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::ToDateTime(), 'DateUpdated' => Gdn_Format::ToDateTime(), 'Name' => 'Root', 'UrlCode' => '', 'Description' => 'Root of category tree. Users should never see this.', 'PermissionCategoryID' => -1));
    $RootCategoryInserted = true;
}

if ($Drop || !$CategoryExists) {
    $SQL->Insert('Category', array('ParentCategoryID' => -1, 'TreeLeft' => 2, 'TreeRight' => 3, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::ToDateTime(), 'DateUpdated' => Gdn_Format::ToDateTime(), 'Name' => 'General', 'UrlCode' => 'general', 'Description' => 'General discussions', 'PermissionCategoryID' => -1));
} elseif ($CategoryExists && !$PermissionCategoryIDExists) {
    if (!C('Garden.Permissions.Disabled.Category')) {
        // Existing installations need to be set up with per/category permissions.
        $SQL->Update('Category')->Set('PermissionCategoryID', 'CategoryID', false)->Put();
        $SQL->Update('Permission')->Set('JunctionColumn', 'PermissionCategoryID')->Where('JunctionColumn', 'CategoryID')->Put();
    }
}

if ($CategoryExists) {
    $CategoryModel = new CategoryModel();
    $CategoryModel->RebuildTree();
    unset($CategoryModel);
}

// Construct the discussion table.
$Construct->Table('Discussion');
$DiscussionExists = $Construct->TableExists();
$FirstCommentIDExists = $Construct->ColumnExists('FirstCommentID');
$BodyExists = $Construct->ColumnExists('Body');
$LastCommentIDExists = $Construct->ColumnExists('LastCommentID');
$LastCommentUserIDExists = $Construct->ColumnExists('LastCommentUserID');
$CountBookmarksExists = $Construct->ColumnExists('CountBookmarks');

$Construct
    ->PrimaryKey('DiscussionID')
    ->Column('Type', 'varchar(10)', true, 'index')
    ->Column('ForeignID', 'varchar(32)', true, 'index')// For relating foreign records to discussions
    ->Column('CategoryID', 'int', false, array('index.CategoryPages', 'index.CategoryInserted'))
    ->Column('InsertUserID', 'int', false, 'key')
    ->Column('UpdateUserID', 'int', true)
    ->Column('FirstCommentID', 'int', true)
    ->Column('LastCommentID', 'int', true)
    ->Column('Name', 'varchar(100)', false, 'fulltext')
    ->Column('Body', 'text', false, 'fulltext')
    ->Column('Format', 'varchar(20)', true)
    ->Column('Tags', 'text', null)
    ->Column('CountComments', 'int', '0')
    ->Column('CountBookmarks', 'int', null)
    ->Column('CountViews', 'int', '1')
    ->Column('Closed', 'tinyint(1)', '0')
    ->Column('Announce', 'tinyint(1)', '0')
    ->Column('Sink', 'tinyint(1)', '0')
    ->Column('DateInserted', 'datetime', false, array('index', 'index.CategoryInserted'))
    ->Column('DateUpdated', 'datetime', true)
    ->Column('InsertIPAddress', 'varchar(15)', true)
    ->Column('UpdateIPAddress', 'varchar(15)', true)
    ->Column('DateLastComment', 'datetime', null, array('index', 'index.CategoryPages'))
    ->Column('LastCommentUserID', 'int', true)
    ->Column('Score', 'float', null)
    ->Column('Attributes', 'text', true)
    ->Column('RegardingID', 'int(11)', true, 'index');
//->Column('Source', 'varchar(20)', TRUE)

if (C('Vanilla.QueueNotifications')) {
    $Construct->Column('Notified', 'tinyint', ActivityModel::SENT_ARCHIVE);
}

$Construct
    ->Set($Explicit, $Drop);

if ($DiscussionExists && !$FirstCommentIDExists) {
    $Px = $SQL->Database->DatabasePrefix;
    $UpdateSQL = "update {$Px}Discussion d set FirstCommentID = (select min(c.CommentID) from {$Px}Comment c where c.DiscussionID = d.DiscussionID)";
    $SQL->Query($UpdateSQL, 'update');
}

$Construct->Table('UserCategory')
    ->Column('UserID', 'int', false, 'primary')
    ->Column('CategoryID', 'int', false, 'primary')
    ->Column('DateMarkedRead', 'datetime', null)
    ->Column('Unfollow', 'tinyint(1)', 0)
    ->Set($Explicit, $Drop);

// Allows the tracking of relationships between discussions and users (bookmarks, dismissed announcements, # of read comments in a discussion, etc)
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->Table('UserDiscussion');

$ParticipatedExists = $Construct->ColumnExists('Participated');

$Construct->Column('UserID', 'int', false, 'primary')
    ->Column('DiscussionID', 'int', false, array('primary', 'key'))
    ->Column('Score', 'float', null)
    ->Column('CountComments', 'int', '0')
    ->Column('DateLastViewed', 'datetime', null)// null signals never
    ->Column('Dismissed', 'tinyint(1)', '0')// relates to dismissed announcements
    ->Column('Bookmarked', 'tinyint(1)', '0')
    ->Column('Participated', 'tinyint(1)', '0')// whether or not the user has participated in the discussion.
    ->Set($Explicit, $Drop);

$Construct->Table('Comment');

if ($Construct->TableExists()) {
    $CommentIndexes = $Construct->IndexSqlDb();
} else {
    $CommentIndexes = array();
}

$Construct
    ->Table('Comment')
    ->PrimaryKey('CommentID')
    ->Column('DiscussionID', 'int', false, 'index.1')
    //->Column('Type', 'varchar(10)', TRUE)
    //->Column('ForeignID', 'varchar(32)', TRUE, 'index') // For relating foreign records to discussions
    ->Column('InsertUserID', 'int', true, 'key')
    ->Column('UpdateUserID', 'int', true)
    ->Column('DeleteUserID', 'int', true)
    ->Column('Body', 'text', false, 'fulltext')
    ->Column('Format', 'varchar(20)', true)
    ->Column('DateInserted', 'datetime', null, array('index.1', 'index'))
    ->Column('DateDeleted', 'datetime', true)
    ->Column('DateUpdated', 'datetime', true)
    ->Column('InsertIPAddress', 'varchar(15)', true)
    ->Column('UpdateIPAddress', 'varchar(15)', true)
    ->Column('Flag', 'tinyint', 0)
    ->Column('Score', 'float', null)
    ->Column('Attributes', 'text', true)
    //->Column('Source', 'varchar(20)', TRUE)
    ->Set($Explicit, $Drop);

if (isset($CommentIndexes['FK_Comment_DiscussionID'])) {
    $Construct->Query("drop index FK_Comment_DiscussionID on {$Px}Comment");
}
if (isset($CommentIndexes['FK_Comment_DateInserted'])) {
    $Construct->Query("drop index FK_Comment_DateInserted on {$Px}Comment");
}

// Update the participated flag.
if (!$ParticipatedExists) {
    $SQL->Update('UserDiscussion ud')
        ->Join('Discussion d', 'ud.DiscussionID = d.DiscussionID and ud.UserID = d.InsertUserID')
        ->Set('ud.Participated', 1)
        ->Put();

    $SQL->Update('UserDiscussion ud')
        ->Join('Comment d', 'ud.DiscussionID = d.DiscussionID and ud.UserID = d.InsertUserID')
        ->Set('ud.Participated', 1)
        ->Put();
}

// Allows the tracking of already-read comments & votes on a per-user basis.
$Construct->Table('UserComment')
    ->Column('UserID', 'int', false, 'primary')
    ->Column('CommentID', 'int', false, 'primary')
    ->Column('Score', 'float', null)
    ->Column('DateLastViewed', 'datetime', null)// null signals never
    ->Set($Explicit, $Drop);

// Add extra columns to user table for tracking discussions & comments
$Construct->Table('User')
    ->Column('CountDiscussions', 'int', null)
    ->Column('CountUnreadDiscussions', 'int', null)
    ->Column('CountComments', 'int', null)
    ->Column('CountDrafts', 'int', null)
    ->Column('CountBookmarks', 'int', null)
    ->Set();

$Construct->Table('Draft')
    ->PrimaryKey('DraftID')
    ->Column('DiscussionID', 'int', true, 'key')
    ->Column('CategoryID', 'int', true, 'key')
    ->Column('InsertUserID', 'int', false, 'key')
    ->Column('UpdateUserID', 'int')
    ->Column('Name', 'varchar(100)', true)
    ->Column('Tags', 'varchar(255)', null)
    ->Column('Closed', 'tinyint(1)', '0')
    ->Column('Announce', 'tinyint(1)', '0')
    ->Column('Sink', 'tinyint(1)', '0')
    ->Column('Body', 'text')
    ->Column('Format', 'varchar(20)', true)
    ->Column('DateInserted', 'datetime')
    ->Column('DateUpdated', 'datetime', true)
    ->Set($Explicit, $Drop);

// Insert some activity types
///  %1 = ActivityName
///  %2 = ActivityName Possessive
///  %3 = RegardingName
///  %4 = RegardingName Possessive
///  %5 = Link to RegardingName's Wall
///  %6 = his/her
///  %7 = he/she
///  %8 = RouteCode & Route

// X added a discussion
if ($SQL->GetWhere('ActivityType', array('Name' => 'NewDiscussion'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'NewDiscussion', 'FullHeadline' => '%1$s started a %8$s.', 'ProfileHeadline' => '%1$s started a %8$s.', 'RouteCode' => 'discussion', 'Public' => '0'));
}

// X commented on a discussion.
if ($SQL->GetWhere('ActivityType', array('Name' => 'NewComment'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'NewComment', 'FullHeadline' => '%1$s commented on a discussion.', 'ProfileHeadline' => '%1$s commented on a discussion.', 'RouteCode' => 'discussion', 'Public' => '0'));
}

// People's comments on discussions
if ($SQL->GetWhere('ActivityType', array('Name' => 'DiscussionComment'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'DiscussionComment', 'FullHeadline' => '%1$s commented on %4$s %8$s.', 'ProfileHeadline' => '%1$s commented on %4$s %8$s.', 'RouteCode' => 'discussion', 'Notify' => '1', 'Public' => '0'));
}

// People mentioning others in discussion topics
if ($SQL->GetWhere('ActivityType', array('Name' => 'DiscussionMention'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'DiscussionMention', 'FullHeadline' => '%1$s mentioned %3$s in a %8$s.', 'ProfileHeadline' => '%1$s mentioned %3$s in a %8$s.', 'RouteCode' => 'discussion', 'Notify' => '1', 'Public' => '0'));
}

// People mentioning others in comments
if ($SQL->GetWhere('ActivityType', array('Name' => 'CommentMention'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'CommentMention', 'FullHeadline' => '%1$s mentioned %3$s in a %8$s.', 'ProfileHeadline' => '%1$s mentioned %3$s in a %8$s.', 'RouteCode' => 'comment', 'Notify' => '1', 'Public' => '0'));
}

// People commenting on user's bookmarked discussions
if ($SQL->GetWhere('ActivityType', array('Name' => 'BookmarkComment'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'BookmarkComment', 'FullHeadline' => '%1$s commented on your %8$s.', 'ProfileHeadline' => '%1$s commented on your %8$s.', 'RouteCode' => 'bookmarked discussion', 'Notify' => '1', 'Public' => '0'));
}

$ActivityModel = new ActivityModel();
$ActivityModel->DefineType('Discussion');
$ActivityModel->DefineType('Comment');

$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Database = $Database;
$PermissionModel->SQL = $SQL;

// Define some global vanilla permissions.
$PermissionModel->Define(array(
    'Vanilla.Approval.Require',
    'Vanilla.Comments.Me' => 1,
));
$PermissionModel->Undefine(array('Vanilla.Settings.Manage', 'Vanilla.Categories.Manage'));

// Define some permissions for the Vanilla categories.
$PermissionModel->Define(
    array(
    'Vanilla.Discussions.View' => 1,
    'Vanilla.Discussions.Add' => 1,
    'Vanilla.Discussions.Edit' => 0,
    'Vanilla.Discussions.Announce' => 0,
    'Vanilla.Discussions.Sink' => 0,
    'Vanilla.Discussions.Close' => 0,
    'Vanilla.Discussions.Delete' => 0,
    'Vanilla.Comments.Add' => 1,
    'Vanilla.Comments.Edit' => 0,
    'Vanilla.Comments.Delete' => 0),
    'tinyint',
    'Category',
    'PermissionCategoryID'
);

$PermissionModel->Undefine('Vanilla.Spam.Manage');


/*
Apr 26th, 2010
Removed FirstComment from :_Discussion and moved it into the discussion table.
*/
$Prefix = $SQL->Database->DatabasePrefix;

if ($FirstCommentIDExists && !$BodyExists) {
    $Construct->Query("update {$Prefix}Discussion, {$Prefix}Comment
   set {$Prefix}Discussion.Body = {$Prefix}Comment.Body,
      {$Prefix}Discussion.Format = {$Prefix}Comment.Format
   where {$Prefix}Discussion.FirstCommentID = {$Prefix}Comment.CommentID");

    $Construct->Query("delete {$Prefix}Comment
   from {$Prefix}Comment inner join {$Prefix}Discussion
   where {$Prefix}Comment.CommentID = {$Prefix}Discussion.FirstCommentID");
}

if (!$LastCommentIDExists || !$LastCommentUserIDExists) {
    $Construct->Query("update {$Prefix}Discussion d
   inner join {$Prefix}Comment c
      on c.DiscussionID = d.DiscussionID
   inner join (
      select max(c2.CommentID) as CommentID
      from {$Prefix}Comment c2
      group by c2.DiscussionID
   ) c2
   on c.CommentID = c2.CommentID
   set d.LastCommentID = c.CommentID,
      d.LastCommentUserID = c.InsertUserID
where d.LastCommentUserID is null");
}

if (!$CountBookmarksExists) {
    $Construct->Query("update {$Prefix}Discussion d
   set CountBookmarks = (
      select count(ud.DiscussionID)
      from {$Prefix}UserDiscussion ud
      where ud.Bookmarked = 1
         and ud.DiscussionID = d.DiscussionID
   )");
}

$Construct->Table('TagDiscussion');
$DateInsertedExists = $Construct->ColumnExists('DateInserted');

$Construct
    ->Column('TagID', 'int', false, 'primary')
    ->Column('DiscussionID', 'int', false, 'primary')
    ->Column('CategoryID', 'int', false, 'index')
    ->Column('DateInserted', 'datetime', !$DateInsertedExists)
    ->Engine('InnoDB')
    ->Set($Explicit, $Drop);

if (!$DateInsertedExists) {
    $SQL->Update('TagDiscussion td')
        ->Join('Discussion d', 'td.DiscussionID = d.DiscussionID')
        ->Set('td.DateInserted', 'd.DateInserted', false, false)
        ->Put();
}

$Construct->Table('Tag')
    ->Column('CountDiscussions', 'int', 0)
    ->Set();

$Categories = Gdn::SQL()->Where("coalesce(UrlCode, '') =", "''", false, false)->Get('Category')->ResultArray();
foreach ($Categories as $Category) {
    $UrlCode = Gdn_Format::Url($Category['Name']);
    if (strlen($UrlCode) > 50) {
        $UrlCode = $Category['CategoryID'];
    }

    Gdn::SQL()->Put(
        'Category',
        array('UrlCode' => $UrlCode),
        array('CategoryID' => $Category['CategoryID'])
    );
}

// Moved this down here because it needs to run after GDN_Comment is created
if (!$LastDiscussionIDExists) {
    $SQL->Update('Category c')
        ->Join('Comment cm', 'c.LastCommentID = cm.CommentID')
        ->Set('c.LastDiscussionID', 'cm.DiscussionID', false, false)
        ->Put();
}

// Add stub content
include(PATH_APPLICATIONS.DS.'vanilla'.DS.'settings'.DS.'stub.php');

// Set current Vanilla.Version
$ApplicationInfo = array();
include(CombinePaths(array(PATH_APPLICATIONS.DS.'vanilla'.DS.'settings'.DS.'about.php')));
$Version = ArrayValue('Version', ArrayValue('Vanilla', $ApplicationInfo, array()), 'Undefined');
SaveToConfig('Vanilla.Version', $Version);
