<?php if (!defined('APPLICATION')) exit();
/**
 * Vanilla database structure.
 *
 * Called by VanillaHooks::setup() to update database upon enabling app.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @since 2.0
 * @package Vanilla
 */

if (!isset($Drop)) {
    $Drop = false;
}

if (!isset($Explicit)) {
    $Explicit = false;
}

$SQL = Gdn::database()->sql();
$Construct = Gdn::database()->structure();
$Px = $Construct->databasePrefix();

$captureOnly = Gdn::database()->structure()->CaptureOnly;

$Construct->table('Category');
$CategoryExists = $Construct->tableExists();
$CountCategoriesExists = $Construct->columnExists('CountCategories');
$PermissionCategoryIDExists = $Construct->columnExists('PermissionCategoryID');

$LastDiscussionIDExists = $Construct->columnExists('LastDiscussionID');

$CountAllDiscussionsExists = $Construct->columnExists('CountAllDiscussions');
$CountAllCommentsExists = $Construct->columnExists('CountAllComments');

$Construct->primaryKey('CategoryID')
    ->column('ParentCategoryID', 'int', true, 'key')
    ->column('TreeLeft', 'int', true)
    ->column('TreeRight', 'int', true)
    ->column('Depth', 'int', '0')
    ->column('CountCategories', 'int', '0')
    ->column('CountDiscussions', 'int', '0')
    ->column('CountAllDiscussions', 'int', '0')
    ->column('CountComments', 'int', '0')
    ->column('CountAllComments', 'int', '0')
    ->column('LastCategoryID', 'int', '0')
    ->column('DateMarkedRead', 'datetime', null)
    ->column('AllowDiscussions', 'tinyint', '1')
    ->column('Archived', 'tinyint(1)', '0')
    ->column('CanDelete', 'tinyint', '1')
    ->column('Name', 'varchar(255)')
    ->column('UrlCode', 'varchar(255)', true)
    ->column('Description', 'varchar(500)', true)
    ->column('Sort', 'int', true)
    ->column('CssClass', 'varchar(50)', true)
    ->column('Photo', 'varchar(255)', true)
    ->column('PermissionCategoryID', 'int', '-1')// default to root.
    ->column('PointsCategoryID', 'int', '0')// default to global.
    ->column('HideAllDiscussions', 'tinyint(1)', '0')
    ->column('DisplayAs', ['Categories', 'Discussions', 'Flat', 'Heading', 'Default'], 'Discussions')
    ->column('InsertUserID', 'int', false, 'key')
    ->column('UpdateUserID', 'int', true)
    ->column('DateInserted', 'datetime')
    ->column('DateUpdated', 'datetime')
    ->column('LastCommentID', 'int', null)
    ->column('LastDiscussionID', 'int', null)
    ->column('LastDateInserted', 'datetime', null)
    ->column('AllowedDiscussionTypes', 'varchar(255)', null)
    ->column('DefaultDiscussionType', 'varchar(10)', null)
    ->set($Explicit, $Drop);

$RootCategoryInserted = false;
if ($SQL->getWhere('Category', ['CategoryID' => -1])->numRows() == 0) {
    $SQL->insert(
        'Category',
        [
            'CategoryID' => -1,
            'TreeLeft' => 1,
            'TreeRight' => 4,
            'InsertUserID' => 1,
            'UpdateUserID' => 1,
            'DateInserted' => Gdn_Format::toDateTime(),
            'DateUpdated' => Gdn_Format::toDateTime(),
            'Name' => 'Root',
            'UrlCode' => '',
            'Description' => 'Root of category tree. Users should never see this.',
            'PermissionCategoryID' => -1,
            'DisplayAs' => 'Categories'
        ]
    );
    $RootCategoryInserted = true;
}

if ($Drop || !$CategoryExists) {
    $SQL->insert('Category', ['ParentCategoryID' => -1, 'TreeLeft' => 2, 'TreeRight' => 3, 'Depth' => 1, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::toDateTime(), 'DateUpdated' => Gdn_Format::toDateTime(), 'Name' => 'General', 'UrlCode' => 'general', 'Description' => 'General discussions', 'PermissionCategoryID' => -1]);
} elseif ($CategoryExists && !$PermissionCategoryIDExists) {
    if (!c('Garden.Permissions.Disabled.Category')) {
        // Existing installations need to be set up with per/category permissions.
        $SQL->update('Category')->set('PermissionCategoryID', 'CategoryID', false)->put();
        $SQL->update('Permission')->set('JunctionColumn', 'PermissionCategoryID')->where('JunctionColumn', 'CategoryID')->put();
    }
}

if ($CategoryExists) {
    CategoryModel::instance()->rebuildTree();
    CategoryModel::instance()->recalculateTree();
    unset($CategoryModel);
}

// Construct the discussion table.
$Construct->table('Discussion');
$DiscussionExists = $Construct->tableExists();
$FirstCommentIDExists = $Construct->columnExists('FirstCommentID');
$BodyExists = $Construct->columnExists('Body');
$LastCommentIDExists = $Construct->columnExists('LastCommentID');
$LastCommentUserIDExists = $Construct->columnExists('LastCommentUserID');
$CountBookmarksExists = $Construct->columnExists('CountBookmarks');

$Construct
    ->primaryKey('DiscussionID')
    ->column('Type', 'varchar(10)', true, 'index')
    ->column('ForeignID', 'varchar(32)', true, 'index')// For relating foreign records to discussions
    ->column('CategoryID', 'int', false, ['index.CategoryPages', 'index.CategoryInserted'])
    ->column('InsertUserID', 'int', false, 'key')
    ->column('UpdateUserID', 'int', true)
    ->column('FirstCommentID', 'int', true)
    ->column('LastCommentID', 'int', true)
    ->column('Name', 'varchar(100)', false, 'fulltext')
    ->column('Body', 'text', false, 'fulltext')
    ->column('Format', 'varchar(20)', true)
    ->column('Tags', 'text', null)
    ->column('CountComments', 'int', '0')
    ->column('CountBookmarks', 'int', null)
    ->column('CountViews', 'int', '1')
    ->column('Closed', 'tinyint(1)', '0')
    ->column('Announce', 'tinyint(1)', '0')
    ->column('Sink', 'tinyint(1)', '0')
    ->column('DateInserted', 'datetime', false, ['index', 'index.CategoryInserted'])
    ->column('DateUpdated', 'datetime', true)
    ->column('InsertIPAddress', 'ipaddress', true)
    ->column('UpdateIPAddress', 'ipaddress', true)
    ->column('DateLastComment', 'datetime', null, ['index', 'index.CategoryPages'])
    ->column('LastCommentUserID', 'int', true)
    ->column('Score', 'float', null)
    ->column('Attributes', 'text', true)
    ->column('RegardingID', 'int(11)', true, 'index');
//->column('Source', 'varchar(20)', true)

if (c('Vanilla.QueueNotifications')) {
    $Construct->column('Notified', 'tinyint', ActivityModel::SENT_ARCHIVE);
}

$Construct
    ->set($Explicit, $Drop);

if ($DiscussionExists && !$FirstCommentIDExists) {
    $Px = $SQL->Database->DatabasePrefix;
    $UpdateSQL = "update {$Px}Discussion d set FirstCommentID = (select min(c.CommentID) from {$Px}Comment c where c.DiscussionID = d.DiscussionID)";
    $SQL->query($UpdateSQL, 'update');
}

$Construct->table('UserCategory')
    ->column('UserID', 'int', false, 'primary')
    ->column('CategoryID', 'int', false, 'primary')
    ->column('DateMarkedRead', 'datetime', null)
    ->column('Followed', 'tinyint(1)', 0);

// This column should be removed when muting categories is dropped in favor of category following..
$Construct->column('Unfollow', 'tinyint(1)', 0);

$Construct->set($Explicit, $Drop);

// Allows the tracking of relationships between discussions and users (bookmarks, dismissed announcements, # of read comments in a discussion, etc)
// column($Name, $Type, $Length = '', $Null = FALSE, $Default = null, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->table('UserDiscussion');

$ParticipatedExists = $Construct->columnExists('Participated');

$Construct->column('UserID', 'int', false, 'primary')
    ->column('DiscussionID', 'int', false, ['primary', 'key'])
    ->column('Score', 'float', null)
    ->column('CountComments', 'int', '0')
    ->column('DateLastViewed', 'datetime', null)// null signals never
    ->column('Dismissed', 'tinyint(1)', '0')// relates to dismissed announcements
    ->column('Bookmarked', 'tinyint(1)', '0')
    ->column('Participated', 'tinyint(1)', '0')// whether or not the user has participated in the discussion.
    ->set($Explicit, $Drop);

$Construct->table('Comment');

if ($Construct->tableExists()) {
    $CommentIndexes = $Construct->indexSqlDb();
} else {
    $CommentIndexes = [];
}

$Construct
    ->table('Comment')
    ->primaryKey('CommentID')
    ->column('DiscussionID', 'int', false, 'index.1')
    //->column('Type', 'varchar(10)', true)
    //->column('ForeignID', 'varchar(32)', TRUE, 'index') // For relating foreign records to discussions
    ->column('InsertUserID', 'int', true, 'key')
    ->column('UpdateUserID', 'int', true)
    ->column('DeleteUserID', 'int', true)
    ->column('Body', 'text', false, 'fulltext')
    ->column('Format', 'varchar(20)', true)
    ->column('DateInserted', 'datetime', null, ['index.1', 'index'])
    ->column('DateDeleted', 'datetime', true)
    ->column('DateUpdated', 'datetime', true)
    ->column('InsertIPAddress', 'ipaddress', true)
    ->column('UpdateIPAddress', 'ipaddress', true)
    ->column('Flag', 'tinyint', 0)
    ->column('Score', 'float', null)
    ->column('Attributes', 'text', true)
    //->column('Source', 'varchar(20)', true)
    ->set($Explicit, $Drop);

if (isset($CommentIndexes['FK_Comment_DiscussionID'])) {
    $SQL->query("drop index FK_Comment_DiscussionID on {$Px}Comment");
}
if (isset($CommentIndexes['FK_Comment_DateInserted'])) {
    $SQL->query("drop index FK_Comment_DateInserted on {$Px}Comment");
}

// Update the participated flag.
if (!$ParticipatedExists) {
    $SQL->update('UserDiscussion ud')
        ->join('Discussion d', 'ud.DiscussionID = d.DiscussionID and ud.UserID = d.InsertUserID')
        ->set('ud.Participated', 1)
        ->put();

    $SQL->update('UserDiscussion ud')
        ->join('Comment d', 'ud.DiscussionID = d.DiscussionID and ud.UserID = d.InsertUserID')
        ->set('ud.Participated', 1)
        ->put();
}

// Allows the tracking of already-read comments & votes on a per-user basis.
$Construct->table('UserComment')
    ->column('UserID', 'int', false, 'primary')
    ->column('CommentID', 'int', false, 'primary')
    ->column('Score', 'float', null)
    ->column('DateLastViewed', 'datetime', null)// null signals never
    ->set($Explicit, $Drop);

// Add extra columns to user table for tracking discussions & comments
$Construct->table('User')
    ->column('CountDiscussions', 'int', null)
    ->column('CountUnreadDiscussions', 'int', null)
    ->column('CountComments', 'int', null)
    ->column('CountDrafts', 'int', null)
    ->column('CountBookmarks', 'int', null)
    ->set();

$Construct->table('Draft')
    ->primaryKey('DraftID')
    ->column('DiscussionID', 'int', true, 'key')
    ->column('CategoryID', 'int', true, 'key')
    ->column('InsertUserID', 'int', false, 'key')
    ->column('UpdateUserID', 'int')
    ->column('Name', 'varchar(100)', true)
    ->column('Tags', 'varchar(255)', null)
    ->column('Closed', 'tinyint(1)', '0')
    ->column('Announce', 'tinyint(1)', '0')
    ->column('Sink', 'tinyint(1)', '0')
    ->column('Body', 'text')
    ->column('Format', 'varchar(20)', true)
    ->column('DateInserted', 'datetime')
    ->column('DateUpdated', 'datetime', true)
    ->set($Explicit, $Drop);

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
if ($SQL->getWhere('ActivityType', ['Name' => 'NewDiscussion'])->numRows() == 0) {
    $SQL->insert('ActivityType', ['AllowComments' => '0', 'Name' => 'NewDiscussion', 'FullHeadline' => '%1$s started a %8$s.', 'ProfileHeadline' => '%1$s started a %8$s.', 'RouteCode' => 'discussion', 'Public' => '0']);
}

// X commented on a discussion.
if ($SQL->getWhere('ActivityType', ['Name' => 'NewComment'])->numRows() == 0) {
    $SQL->insert('ActivityType', ['AllowComments' => '0', 'Name' => 'NewComment', 'FullHeadline' => '%1$s commented on a discussion.', 'ProfileHeadline' => '%1$s commented on a discussion.', 'RouteCode' => 'discussion', 'Public' => '0']);
}

// People's comments on discussions
if ($SQL->getWhere('ActivityType', ['Name' => 'DiscussionComment'])->numRows() == 0) {
    $SQL->insert('ActivityType', ['AllowComments' => '0', 'Name' => 'DiscussionComment', 'FullHeadline' => '%1$s commented on %4$s %8$s.', 'ProfileHeadline' => '%1$s commented on %4$s %8$s.', 'RouteCode' => 'discussion', 'Notify' => '1', 'Public' => '0']);
}

// People mentioning others in discussion topics
if ($SQL->getWhere('ActivityType', ['Name' => 'DiscussionMention'])->numRows() == 0) {
    $SQL->insert('ActivityType', ['AllowComments' => '0', 'Name' => 'DiscussionMention', 'FullHeadline' => '%1$s mentioned %3$s in a %8$s.', 'ProfileHeadline' => '%1$s mentioned %3$s in a %8$s.', 'RouteCode' => 'discussion', 'Notify' => '1', 'Public' => '0']);
}

// People mentioning others in comments
if ($SQL->getWhere('ActivityType', ['Name' => 'CommentMention'])->numRows() == 0) {
    $SQL->insert('ActivityType', ['AllowComments' => '0', 'Name' => 'CommentMention', 'FullHeadline' => '%1$s mentioned %3$s in a %8$s.', 'ProfileHeadline' => '%1$s mentioned %3$s in a %8$s.', 'RouteCode' => 'comment', 'Notify' => '1', 'Public' => '0']);
}

// People commenting on user's bookmarked discussions
if ($SQL->getWhere('ActivityType', ['Name' => 'BookmarkComment'])->numRows() == 0) {
    $SQL->insert('ActivityType', ['AllowComments' => '0', 'Name' => 'BookmarkComment', 'FullHeadline' => '%1$s commented on your %8$s.', 'ProfileHeadline' => '%1$s commented on your %8$s.', 'RouteCode' => 'bookmarked discussion', 'Notify' => '1', 'Public' => '0']);
}

$ActivityModel = new ActivityModel();
$ActivityModel->defineType('Discussion');
$ActivityModel->defineType('Comment');

$PermissionModel = Gdn::permissionModel();
$PermissionModel->Database = Gdn::database();
$PermissionModel->SQL = $SQL;

// Define some global vanilla permissions.
$PermissionModel->define([
    'Vanilla.Approval.Require',
    'Vanilla.Comments.Me' => 1,
]);
$PermissionModel->undefine(['Vanilla.Settings.Manage', 'Vanilla.Categories.Manage']);

// Define some permissions for the Vanilla categories.
$PermissionModel->define(
    [
    'Vanilla.Discussions.View' => 1,
    'Vanilla.Discussions.Add' => 1,
    'Vanilla.Discussions.Edit' => 0,
    'Vanilla.Discussions.Announce' => 0,
    'Vanilla.Discussions.Sink' => 0,
    'Vanilla.Discussions.Close' => 0,
    'Vanilla.Discussions.Delete' => 0,
    'Vanilla.Comments.Add' => 1,
    'Vanilla.Comments.Edit' => 0,
    'Vanilla.Comments.Delete' => 0],
    'tinyint',
    'Category',
    'PermissionCategoryID'
);

$PermissionModel->undefine('Vanilla.Spam.Manage');

/*
Apr 26th, 2010
Removed FirstComment from :_Discussion and moved it into the discussion table.
*/
$Prefix = $SQL->Database->DatabasePrefix;

if ($FirstCommentIDExists && !$BodyExists) {
    $SQL->query("update {$Prefix}Discussion, {$Prefix}Comment
   set {$Prefix}Discussion.Body = {$Prefix}Comment.Body,
      {$Prefix}Discussion.Format = {$Prefix}Comment.Format
   where {$Prefix}Discussion.FirstCommentID = {$Prefix}Comment.CommentID");

    $SQL->query("delete {$Prefix}Comment
   from {$Prefix}Comment inner join {$Prefix}Discussion
   where {$Prefix}Comment.CommentID = {$Prefix}Discussion.FirstCommentID");
}

if (!$LastCommentIDExists || !$LastCommentUserIDExists) {
    $SQL->query("update {$Prefix}Discussion d
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
    $SQL->query("update {$Prefix}Discussion d
   set CountBookmarks = (
      select count(ud.DiscussionID)
      from {$Prefix}UserDiscussion ud
      where ud.Bookmarked = 1
         and ud.DiscussionID = d.DiscussionID
   )");
}

$Construct->table('TagDiscussion');
$DateInsertedExists = $Construct->columnExists('DateInserted');

$Construct
    ->column('TagID', 'int', false, 'primary')
    ->column('DiscussionID', 'int', false, 'primary')
    ->column('CategoryID', 'int', false, 'index')
    ->column('DateInserted', 'datetime', !$DateInsertedExists)
    ->engine('InnoDB')
    ->set($Explicit, $Drop);

if (!$DateInsertedExists) {
    $SQL->update('TagDiscussion td')
        ->join('Discussion d', 'td.DiscussionID = d.DiscussionID')
        ->set('td.DateInserted', 'd.DateInserted', false, false)
        ->put();
}

$Construct->table('Tag')
    ->column('CountDiscussions', 'int', 0)
    ->set();

$Categories = Gdn::sql()->where("coalesce(UrlCode, '') =", "''", false, false)->get('Category')->resultArray();
foreach ($Categories as $Category) {
    $UrlCode = Gdn_Format::url($Category['Name']);
    if (strlen($UrlCode) > 50) {
        $UrlCode = $Category['CategoryID'];
    }

    Gdn::sql()->put(
        'Category',
        ['UrlCode' => $UrlCode],
        ['CategoryID' => $Category['CategoryID']]
    );
}

// Moved this down here because it needs to run after GDN_Comment is created
if (!$LastDiscussionIDExists) {
    $SQL->update('Category c')
        ->join('Comment cm', 'c.LastCommentID = cm.CommentID')
        ->set('c.LastDiscussionID', 'cm.DiscussionID', false, false)
        ->put();
}

if (!$captureOnly) {
    if (!$CountAllDiscussionsExists) {
        CategoryModel::instance()->counts('CountAllDiscussions');
    }
    if (!$CountAllCommentsExists) {
        CategoryModel::instance()->counts('CountAllComments');
    }
}

// Add stub content
include(PATH_APPLICATIONS.DS.'vanilla'.DS.'settings'.DS.'stub.php');

// Set current Vanilla.Version
$appInfo = json_decode(file_get_contents(PATH_APPLICATIONS.DS.'vanilla'.DS.'addon.json'), true);
saveToConfig('Vanilla.Version', val('version', $appInfo, 'Undefined'));
