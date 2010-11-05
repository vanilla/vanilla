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
   
$SQL = $Database->SQL();
$Construct = $Database->Structure();

$Construct->Table('Category')
   ->PrimaryKey('CategoryID')
   ->Column('ParentCategoryID', 'int', TRUE)
   ->Column('CountDiscussions', 'int', '0')
   ->Column('AllowDiscussions', 'tinyint', '1')
   ->Column('Name', 'varchar(30)')
   ->Column('UrlCode', 'varchar(30)', TRUE)
   ->Column('Description', 'varchar(250)', TRUE)
   ->Column('Sort', 'int', TRUE)
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime')
   ->Set($Explicit, $Drop);

if ($Drop)
   $SQL->Insert('Category', array('InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::ToDateTime(), 'DateUpdated' => Gdn_Format::ToDateTime(), 'Name' => 'General', 'UrlCode' => 'general', 'Description' => 'General discussions', 'Sort' => '1'));

// Construct the discussion table.
$Construct->Table('Discussion');

$FirstCommentIDExists = $Construct->ColumnExists('FirstCommentID');
$BodyExists = $Construct->ColumnExists('Body');
$LastCommentIDExists = $Construct->ColumnExists('LastCommentID');
$LastCommentUserIDExists = $Construct->ColumnExists('LastCommentUserID');
$CountBookmarksExists = $Construct->ColumnExists('CountBookmarks');

$Construct
   ->PrimaryKey('DiscussionID')
   ->Column('CategoryID', 'int', FALSE, 'key')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('UpdateUserID', 'int')
   ->Column('LastCommentID', 'int', TRUE)
   ->Column('Name', 'varchar(100)', FALSE, 'fulltext')
	->Column('Body', 'text', FALSE, 'fulltext')
	->Column('Format', 'varchar(20)', TRUE)
   ->Column('Tags', 'varchar(255)', NULL)
   ->Column('CountComments', 'int', '1')
   ->Column('CountBookmarks', 'int', NULL)
   ->Column('CountViews', 'int', '1')
   ->Column('Closed', 'tinyint(1)', '0')
   ->Column('Announce', 'tinyint(1)', '0')
   ->Column('Sink', 'tinyint(1)', '0')
   ->Column('DateInserted', 'datetime', NULL)
   ->Column('DateUpdated', 'datetime')
   ->Column('DateLastComment', 'datetime', NULL, 'index')
	->Column('LastCommentUserID', 'int', TRUE)
	->Column('Score', 'float', NULL)
   ->Column('Attributes', 'text', TRUE)
   ->Engine('MyISAM')
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
   ->Column('Bookmarked', 'tinyint(1)', '0');
$Construct
   ->Set($Explicit, $Drop);

$Construct->Table('Comment')
	->PrimaryKey('CommentID')
	->Column('DiscussionID', 'int', FALSE, 'key')
	->Column('InsertUserID', 'int', TRUE, 'key')
	->Column('UpdateUserID', 'int', TRUE)
	->Column('DeleteUserID', 'int', TRUE)
	->Column('Body', 'text', FALSE, 'fulltext')
	->Column('Format', 'varchar(20)', TRUE)
	->Column('DateInserted', 'datetime', NULL, 'key')
	->Column('DateDeleted', 'datetime', TRUE)
	->Column('DateUpdated', 'datetime', TRUE)
	->Column('Flag', 'tinyint', 0)
	->Column('Score', 'float', NULL)
	->Column('Attributes', 'text', TRUE)
	->Engine('MyISAM')
	->Set($Explicit, $Drop);

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

$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Database = $Database;
$PermissionModel->SQL = $SQL;

// Define some global vanilla permissions.
$PermissionModel->Define(array(
	'Vanilla.Settings.Manage',
	'Vanilla.Categories.Manage',
	'Vanilla.Spam.Manage'
	));

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
	'CategoryID'
	);

if ($Drop) {
   // Get the general category so we can assign permissions to it.
   $GeneralCategoryID = $SQL->GetWhere('Category', array('Name' => 'General'))->Value('CategoryID', 0);
   
   // Set the initial guest permissions.
   $PermissionModel->Save(array(
      'RoleID' => 2,
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'CategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Discussions.View' => 1
      ), TRUE);
   
   // Set the intial member permissions.
   $PermissionModel->Save(array(
      'RoleID' => 8,
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'CategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Discussions.Add' => 1,
      'Vanilla.Discussions.View' => 1,
      'Vanilla.Comments.Add' => 1
      ), TRUE);
      
   // Set the initial moderator permissions.
   $PermissionModel->Save(array(
      'RoleID' => 32,
      'Vanilla.Categories.Manage' => 1,
      'Vanilla.Spam.Manage' => 1,
      ), TRUE);
   
   $PermissionModel->Save(array(
      'RoleID' => 32,
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'CategoryID',
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
      
   // Set the initial administrator permissions.
   $PermissionModel->Save(array(
      'RoleID' => 16,
      'Vanilla.Settings.Manage' => 1,
      'Vanilla.Categories.Manage' => 1,
      'Vanilla.Spam.Manage' => 1,
      ), TRUE);
   
   $PermissionModel->Save(array(
      'RoleID' => 16,
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'CategoryID',
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
      'RoleID' => 32,
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'CategoryID',
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
   
   // Make sure that User.Permissions is blank so new permissions for users get applied.
   $SQL->Update('User', array('Permissions' => ''))->Put();
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

// Update lastcommentid & firstcommentid
if ($FirstCommentIDExists)
   $Construct->Query("update {$Prefix}Discussion set LastCommentID = null where LastCommentID = FirstCommentID");

// This is the final structure of the discussion table after removed & updated columns.
if ($FirstCommentIDExists) {
   $Construct->Table('Discussion')->DropColumn('FirstCommentID');
   $Construct->Reset();
}

$Construct->Table('TagDiscussion')
   ->Column('TagID', 'int', FALSE, 'primary')
   ->Column('DiscussionID', 'int', FALSE, 'primary')
   ->Engine('InnoDB')
   ->Set($Explicit, $Drop);

$Construct->Table('Tag')
   ->Column('CountDiscussions', 'int', 0)
   ->Set();
