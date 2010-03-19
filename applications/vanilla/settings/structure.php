<?php if (!defined('APPLICATION')) exit();

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
   ->Column('AllowDiscussions', array('1','0'), '1')
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
   $SQL->Insert('Category', array('InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Format::ToDateTime(), 'DateUpdated' => Format::ToDateTime(), 'Name' => 'General', 'Description' => 'General discussions', 'Sort' => '1'));

// Construct the discussion table.
$Construct->Table('Discussion')
   ->PrimaryKey('DiscussionID')
   ->Column('CategoryID', 'int', FALSE, 'key')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('UpdateUserID', 'int')
   ->Column('FirstCommentID', 'int', TRUE, 'key')
   ->Column('LastCommentID', 'int', TRUE, 'key')
   ->Column('Name', 'varchar(100)', FALSE, 'fulltext')
   ->Column('CountComments', 'int', '1')
   ->Column('Closed', array('1', '0'), '0')
   ->Column('Announce', array('1', '0'), '0')
   ->Column('Sink', array('1', '0'), '0')
   ->Column('DateInserted', 'datetime', NULL, 'key')
   ->Column('DateUpdated', 'datetime')
   ->Column('DateLastComment', 'datetime', NULL, 'index')
   ->Column('Attributes', 'text', TRUE)
   ->Set($Explicit, $Drop);
   
// Allows the tracking of relationships between discussions and users (bookmarks, dismissed announcements, # of read comments in a discussion, etc)
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->Table('UserDiscussion')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('DiscussionID', 'int', FALSE, 'primary')
   ->Column('CountComments', 'int', '0')
   ->Column('DateLastViewed', 'datetime')
   ->Column('Dismissed', 'varchar(1)', TRUE) // Relates to dismissed announcements
   ->Column('Bookmarked', 'varchar(1)', TRUE)
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
   ->Column('Attributes', 'text', TRUE)
   ->Set($Explicit, $Drop);

// Allows the tracking of already-read comments on a per-user basis.
$Construct->Table('CommentWatch')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('CommentID', 'int', FALSE, 'primary')
   ->Column('DateLastViewed', 'datetime')
   ->Set($Explicit, $Drop);
   
// Add extra columns to user table for tracking discussions & comments
$Construct->Table('User')
   ->Column('CountDiscussions', 'int', '0')
   ->Column('CountUnreadDiscussions', 'int', '0')
   ->Column('CountComments', 'int', '0')
   ->Column('CountDrafts', 'int', '0')
   ->Column('CountBookmarks', 'int', '0')
   ->Set();

$Construct->Table('Draft')
   ->PrimaryKey('DraftID')
   ->Column('DiscussionID', 'int', TRUE, 'key')
   ->Column('CategoryID', 'int', TRUE, 'key')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('UpdateUserID', 'int')
   ->Column('Name', 'varchar(100)', TRUE)
   ->Column('Closed', array('1', '0'), '0')
   ->Column('Announce', array('1', '0'), '0')
   ->Column('Sink', array('1', '0'), '0')
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

if ($Drop) {
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
      'Vanilla.Discussions.View',
      'Vanilla.Discussions.Add',
      'Vanilla.Discussions.Edit',
      'Vanilla.Discussions.Announce',
      'Vanilla.Discussions.Sink',
      'Vanilla.Discussions.Close',
      'Vanilla.Discussions.Delete',
      'Vanilla.Comments.Add',
      'Vanilla.Comments.Edit',
      'Vanilla.Comments.Delete'),
      'tinyint',
      'Category',
      'CategoryID'
      );
   
   // Get the general category so we can assign permissions to it.
   $GeneralCategoryID = $SQL->GetWhere('Category', array('Name' => 'General'))->Value('CategoryID', 0);
   
   // Set the initial guest permissions.
   $PermissionModel->Save(array(
      'RoleID' => 2,
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'CategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Discussions.View' => 1
      ));
   
   // Set the intial member permissions.
   $PermissionModel->Save(array(
      'RoleID' => 8,
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'CategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Discussions.Add' => 1,
      'Vanilla.Discussions.View' => 1,
      'Vanilla.Comments.Add' => 1
      ));
      
   // Set the initial administrator permissions.
   $PermissionModel->Save(array(
      'RoleID' => 16,
      'Vanilla.Settings.Manage' => 1,
      'Vanilla.Categories.Manage' => 1,
      'Vanilla.Spam.Manage' => 1,
      ));
   
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
      ));
   
   // Make sure that User.Permissions is blank so new permissions for users get applied.
   $SQL->Update('User', array('Permissions' => ''))->Put();
}