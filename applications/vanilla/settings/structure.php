<?php if (!defined('APPLICATION')) exit();
/**
 * Vanilla database structure
 *
 * Called by VanillaHooks::Setup() to update database upon enabling app.
 *
 * @package Vanilla
 */

if (!isset($Drop))
   $Drop = FALSE;
   
if (!isset($Explicit))
   $Explicit = TRUE;
   
$SQL = Gdn::Database()->SQL();
$Construct = Gdn::Database()->Structure();
$Px = $Construct->DatabasePrefix();

$Construct->Table('Category');
$CategoryExists = $Construct->TableExists();
$PermissionCategoryIDExists = $Construct->ColumnExists('PermissionCategoryID');

$LastDiscussionIDExists = $Construct->ColumnExists('LastDiscussionID');

$Construct->PrimaryKey('CategoryID')
   ->Column('ParentCategoryID', 'int', TRUE)
   ->Column('TreeLeft', 'int', TRUE)
   ->Column('TreeRight', 'int', TRUE)
   ->Column('Depth', 'int', TRUE)
   ->Column('CountDiscussions', 'int', '0')
   ->Column('CountComments', 'int', '0')
   ->Column('DateMarkedRead', 'datetime', NULL)
   ->Column('AllowDiscussions', 'tinyint', '1')
   ->Column('Archived', 'tinyint(1)', '0')
   ->Column('Name', 'varchar(255)')
   ->Column('UrlCode', 'varchar(255)', TRUE)
   ->Column('Description', 'varchar(500)', TRUE)
   ->Column('Sort', 'int', TRUE)
   ->Column('CssClass', 'varchar(50)', TRUE)
   ->Column('Photo', 'varchar(255)', TRUE)
   ->Column('PermissionCategoryID', 'int', '-1') // default to root.
   ->Column('HideAllDiscussions', 'tinyint(1)', '0')
   ->Column('DisplayAs', array('Categories', 'Discussions', 'Default'), 'Default')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime')
   ->Column('LastCommentID', 'int', NULL)
   ->Column('LastDiscussionID', 'int', NULL)
   ->Column('LastDateInserted', 'datetime', NULL)
   ->Set($Explicit, $Drop);

$RootCategoryInserted = FALSE;
if ($SQL->GetWhere('Category', array('CategoryID' => -1))->NumRows() == 0) {
   $SQL->Insert('Category', array('CategoryID' => -1, 'TreeLeft' => 1, 'TreeRight' => 4, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::ToDateTime(), 'DateUpdated' => Gdn_Format::ToDateTime(), 'Name' => 'Root', 'UrlCode' => '', 'Description' => 'Root of category tree. Users should never see this.', 'PermissionCategoryID' => -1));
   $RootCategoryInserted = TRUE;
}

if ($Drop || !$CategoryExists) {
   $SQL->Insert('Category', array('ParentCategoryID' => -1, 'TreeLeft' => 2, 'TreeRight' => 3, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::ToDateTime(), 'DateUpdated' => Gdn_Format::ToDateTime(), 'Name' => 'General', 'UrlCode' => 'general', 'Description' => 'General discussions', 'PermissionCategoryID' => -1));
} elseif ($CategoryExists && !$PermissionCategoryIDExists) {
   if (!C('Garden.Permissions.Disabled.Category')) {
      // Existing installations need to be set up with per/category permissions.
      $SQL->Update('Category')->Set('PermissionCategoryID', 'CategoryID', FALSE)->Put();
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
   ->Column('Type', 'varchar(10)', TRUE, 'index')
   ->Column('ForeignID', 'varchar(32)', TRUE, 'index') // For relating foreign records to discussions
   ->Column('CategoryID', 'int', FALSE, array('key', 'index.CategoryPages'))
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('FirstCommentID', 'int', TRUE)
   ->Column('LastCommentID', 'int', TRUE)
   ->Column('Name', 'varchar(100)', FALSE, 'fulltext')
	->Column('Body', 'text', FALSE, 'fulltext')
	->Column('Format', 'varchar(20)', TRUE)
   ->Column('Tags', 'varchar(255)', NULL)
   ->Column('CountComments', 'int', '0')
   ->Column('CountBookmarks', 'int', NULL)
   ->Column('CountViews', 'int', '1')
   ->Column('Closed', 'tinyint(1)', '0')
   ->Column('Announce', 'tinyint(1)', '0')
   ->Column('Sink', 'tinyint(1)', '0')
   ->Column('DateInserted', 'datetime', FALSE, 'index')
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('InsertIPAddress', 'varchar(39)', TRUE)
   ->Column('UpdateIPAddress', 'varchar(39)', TRUE)
   ->Column('DateLastComment', 'datetime', NULL, array('index', 'index.CategoryPages'))
	->Column('LastCommentUserID', 'int', TRUE)
	->Column('Score', 'float', NULL)
   ->Column('Attributes', 'text', TRUE)
   ->Column('RegardingID', 'int(11)', TRUE, 'index')
   //->Column('Source', 'varchar(20)', TRUE)
   ->Set($Explicit, $Drop);

if ($DiscussionExists && !$FirstCommentIDExists) {
   $Px = $SQL->Database->DatabasePrefix;
   $UpdateSQL = "update {$Px}Discussion d set FirstCommentID = (select min(c.CommentID) from {$Px}Comment c where c.DiscussionID = d.DiscussionID)";
   $SQL->Query($UpdateSQL, 'update');
}

$Construct->Table('UserCategory')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('CategoryID', 'int', FALSE, 'primary')
   ->Column('DateMarkedRead', 'datetime', NULL)
   ->Column('Unfollow', 'tinyint(1)', 0)
   ->Set($Explicit, $Drop);
   
// Allows the tracking of relationships between discussions and users (bookmarks, dismissed announcements, # of read comments in a discussion, etc)
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->Table('UserDiscussion')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('DiscussionID', 'int', FALSE, array('primary', 'key'))
	->Column('Score', 'float', NULL)
   ->Column('CountComments', 'int', '0')
   ->Column('DateLastViewed', 'datetime', NULL) // null signals never
   ->Column('Dismissed', 'tinyint(1)', '0') // relates to dismissed announcements
   ->Column('Bookmarked', 'tinyint(1)', '0')
   ->Set($Explicit, $Drop);

$Construct->Table('Comment');

if ($Construct->TableExists())
   $CommentIndexes = $Construct->IndexSqlDb();
else
   $CommentIndexes = array();

$Construct->PrimaryKey('CommentID')
	->Column('DiscussionID', 'int', FALSE, 'index.1')
   //->Column('Type', 'varchar(10)', TRUE)
   //->Column('ForeignID', 'varchar(32)', TRUE, 'index') // For relating foreign records to discussions
	->Column('InsertUserID', 'int', TRUE, 'key')
	->Column('UpdateUserID', 'int', TRUE)
	->Column('DeleteUserID', 'int', TRUE)
	->Column('Body', 'text', FALSE, 'fulltext')
	->Column('Format', 'varchar(20)', TRUE)
	->Column('DateInserted', 'datetime', NULL, array('index.1', 'index'))
	->Column('DateDeleted', 'datetime', TRUE)
	->Column('DateUpdated', 'datetime', TRUE)
   ->Column('InsertIPAddress', 'varchar(39)', TRUE)
   ->Column('UpdateIPAddress', 'varchar(39)', TRUE)
	->Column('Flag', 'tinyint', 0)
	->Column('Score', 'float', NULL)
	->Column('Attributes', 'text', TRUE)
   //->Column('Source', 'varchar(20)', TRUE)
	->Set($Explicit, $Drop);

if (isset($CommentIndexes['FK_Comment_DiscussionID'])) {
   $Construct->Query("drop index FK_Comment_DiscussionID on {$Px}Comment");
}
if (isset($CommentIndexes['FK_Comment_DateInserted'])) {
   $Construct->Query("drop index FK_Comment_DateInserted on {$Px}Comment");
}

// Allows the tracking of already-read comments & votes on a per-user basis.
$Construct->Table('UserComment')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('CommentID', 'int', FALSE, 'primary')
   ->Column('Score', 'float', NULL)
   ->Column('DateLastViewed', 'datetime', NULL) // null signals never
   ->Set($Explicit, $Drop);
   
// Add extra columns to user table for tracking discussions & comments
$Construct->Table('User')
   ->Column('CountDiscussions', 'int', NULL)
   ->Column('CountUnreadDiscussions', 'int', NULL)
   ->Column('CountComments', 'int', NULL)
   ->Column('CountDrafts', 'int', NULL)
   ->Column('CountBookmarks', 'int', NULL)
   ->Set();

$Construct->Table('Draft')
   ->PrimaryKey('DraftID')
   ->Column('DiscussionID', 'int', TRUE, 'key')
   ->Column('CategoryID', 'int', TRUE, 'key')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('UpdateUserID', 'int')
   ->Column('Name', 'varchar(100)', TRUE)
   ->Column('Tags', 'varchar(255)', NULL)
   ->Column('Closed', 'tinyint(1)', '0')
   ->Column('Announce', 'tinyint(1)', '0')
   ->Column('Sink', 'tinyint(1)', '0')
   ->Column('Body', 'text')
   ->Column('Format', 'varchar(20)', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime', TRUE)
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
if ($SQL->GetWhere('ActivityType', array('Name' => 'NewDiscussion'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'NewDiscussion', 'FullHeadline' => '%1$s started a %8$s.', 'ProfileHeadline' => '%1$s started a %8$s.', 'RouteCode' => 'discussion', 'Public' => '0'));

// X commented on a discussion.
if ($SQL->GetWhere('ActivityType', array('Name' => 'NewComment'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'NewComment', 'FullHeadline' => '%1$s commented on a discussion.', 'ProfileHeadline' => '%1$s commented on a discussion.', 'RouteCode' => 'discussion', 'Public' => '0'));
   
// People's comments on discussions
if ($SQL->GetWhere('ActivityType', array('Name' => 'DiscussionComment'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'DiscussionComment', 'FullHeadline' => '%1$s commented on %4$s %8$s.', 'ProfileHeadline' => '%1$s commented on %4$s %8$s.', 'RouteCode' => 'discussion', 'Notify' => '1', 'Public' => '0'));

// People mentioning others in discussion topics
if ($SQL->GetWhere('ActivityType', array('Name' => 'DiscussionMention'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'DiscussionMention', 'FullHeadline' => '%1$s mentioned %3$s in a %8$s.', 'ProfileHeadline' => '%1$s mentioned %3$s in a %8$s.', 'RouteCode' => 'discussion', 'Notify' => '1', 'Public' => '0'));

// People mentioning others in comments
if ($SQL->GetWhere('ActivityType', array('Name' => 'CommentMention'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'CommentMention', 'FullHeadline' => '%1$s mentioned %3$s in a %8$s.', 'ProfileHeadline' => '%1$s mentioned %3$s in a %8$s.', 'RouteCode' => 'comment', 'Notify' => '1', 'Public' => '0'));

// People commenting on user's bookmarked discussions
if ($SQL->GetWhere('ActivityType', array('Name' => 'BookmarkComment'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'BookmarkComment', 'FullHeadline' => '%1$s commented on your %8$s.', 'ProfileHeadline' => '%1$s commented on your %8$s.', 'RouteCode' => 'bookmarked discussion', 'Notify' => '1', 'Public' => '0'));

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
$PermissionModel->Define(array(
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

if ($RootCategoryInserted) {
   // Get the root category so we can assign permissions to it.
   $GeneralCategoryID = -1; //$SQL->GetWhere('Category', array('Name' => 'General'))->Value('PermissionCategoryID', 0);
   
   // Set the initial guest permissions.
   $PermissionModel->Save(array(
      'Role' => 'Guest',
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'PermissionCategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Discussions.View' => 1
      ), TRUE);

   $PermissionModel->Save(array(
      'Role' => 'Confirm Email',
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'PermissionCategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Discussions.View' => 1
      ), TRUE);

   $PermissionModel->Save(array(
      'Role' => 'Applicant',
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'PermissionCategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Discussions.View' => 1
      ), TRUE);
   
   // Set the intial member permissions.
   $PermissionModel->Save(array(
      'Role' => 'Member',
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'PermissionCategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Discussions.Add' => 1,
      'Vanilla.Discussions.View' => 1,
      'Vanilla.Comments.Add' => 1
      ), TRUE);
      
   $PermissionModel->Save(array(
      'Role' => 'Moderator',
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'PermissionCategoryID',
      'JunctionID' => $GeneralCategoryID,
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
      ), TRUE);
      
   $PermissionModel->Save(array(
      'Role' => 'Administrator',
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'PermissionCategoryID',
      'JunctionID' => $GeneralCategoryID,
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
      ), TRUE);
}


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
   ->Column('TagID', 'int', FALSE, 'primary')
   ->Column('DiscussionID', 'int', FALSE, 'primary')
   ->Column('CategoryID', 'int', FALSE, 'index')
   ->Column('DateInserted', 'datetime', !$DateInsertedExists)
   ->Engine('InnoDB')
   ->Set($Explicit, $Drop);

if (!$DateInsertedExists) {
   $SQL->Update('TagDiscussion td')
      ->Join('Discussion d', 'td.DiscussionID = d.DiscussionID')
      ->Set('td.DateInserted', 'd.DateInserted', FALSE, FALSE)
      ->Put();
}

$Construct->Table('Tag')
   ->Column('CountDiscussions', 'int', 0)
   ->Set();

$Categories = Gdn::SQL()->Where("coalesce(UrlCode, '') =", "''", FALSE, FALSE)->Get('Category')->ResultArray();
foreach ($Categories as $Category) {
   $UrlCode = Gdn_Format::Url($Category['Name']);
   if (strlen($UrlCode) > 50)
      $UrlCode = $Category['CategoryID'];

   Gdn::SQL()->Put(
      'Category',
      array('UrlCode' => $UrlCode),
      array('CategoryID' => $Category['CategoryID']));
}

// Moved this down here because it needs to run after GDN_Comment is created
if (!$LastDiscussionIDExists) {
   $SQL->Update('Category c')
      ->Join('Comment cm', 'c.LastCommentID = cm.CommentID')
      ->Set('c.LastDiscussionID', 'cm.DiscussionID', FALSE, FALSE)
      ->Put();
}
