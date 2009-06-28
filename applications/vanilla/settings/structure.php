<?php if (!defined('APPLICATION')) exit();

if (!isset($Drop))
   $Drop = FALSE;
   
if (!isset($Explicit))
   $Explicit = TRUE;
   
$SQL = $Database->SQL();

if ($Drop) {
   // Insert some permissions for the Vanilla categories
   $Permissions = array();
   $Permissions[] = 'Vanilla.Settings.Manage';
   $Permissions[] = 'Vanilla.Categories.Manage';
   $Permissions[] = 'Vanilla.Spam.Manage';
   if (!is_object($Validation))
      $Validation = new Gdn_Validation();
      
   $PermissionModel = new PermissionModel($Validation);
   $PermissionModel->InsertNew($Permissions);
   $Permissions = array();
   $Permissions[] = 'Vanilla.Discussions.View';
   $Permissions[] = 'Vanilla.Discussions.Add';
   $Permissions[] = 'Vanilla.Discussions.Edit';
   $Permissions[] = 'Vanilla.Discussions.Announce';
   $Permissions[] = 'Vanilla.Discussions.Sink';
   $Permissions[] = 'Vanilla.Discussions.Close';
   $Permissions[] = 'Vanilla.Discussions.Delete';
   $Permissions[] = 'Vanilla.Comments.Add';
   $Permissions[] = 'Vanilla.Comments.Edit';
   $Permissions[] = 'Vanilla.Comments.Delete';
   $PermissionModel->InsertNew($Permissions, 'Category', 'CategoryID');
   // Make sure that User.Permissions is blank so new permissions for users get applied.
   $SQL->Update('User', array('Permissions' => ''))->Put();

   // Fix permissions for Vanilla
   if ($SQL->Select('rp.*')
      ->Select('p.Name', '', 'Permission')
      ->From('RolePermission rp')
      ->Join('Permission p', 'rp.PermissionID = p.PermissionID')
      ->Where('RoleID', '4')
      ->Where('p.Name', 'Vanilla.Discussions.Add')
      ->Get()
      ->NumRows() == 0)
   {
      // Member Role
      $Select = $SQL->Select('4, p.PermissionID, 1')
         ->From('RolePermission rp')
         ->Join('Permission p', 'rp.PermissionID = p.PermissionID')
         ->Where('p.Name', 'Vanilla.Discussions.Add')
         ->GetSelect();
      $SQL->Insert('RolePermission', array('RoleID', 'PermissionID', 'JunctionID'), $Select);
      $Select = $SQL->Select('4, p.PermissionID, 1')
         ->From('RolePermission rp')
         ->Join('Permission p', 'rp.PermissionID = p.PermissionID')
         ->Where('p.Name', 'Vanilla.Comments.Add')
         ->GetSelect();
      $SQL->Insert('RolePermission', array('RoleID', 'PermissionID', 'JunctionID'), $Select);
   }

   // Admin permissions
   $SQL->Delete('RolePermission', array('RoleID' => 5));
   $Select = $SQL->Select('5, PermissionID')->From('Permission')->GetSelect();
   $SQL->Insert('RolePermission', array('RoleID', 'PermissionID'), $Select);
}

$Construct->Table('Category')
   ->Column('CategoryID', 'int', 4, FALSE, NULL, 'primary', TRUE)
   ->Column('ParentCategoryID', 'int', 4, TRUE)
   ->Column('CountDiscussions', 'int', 4, FALSE, '0')
   ->Column('AllowDiscussions', array('1','0'), '', FALSE, '1')
   ->Column('Name', 'varchar', 30)
   ->Column('Description', 'varchar', 250, TRUE)
   ->Column('Sort', 'int', 4, TRUE)
   ->Column('InsertUserID', 'int', 10, FALSE, NULL, 'key')
   ->Column('UpdateUserID', 'int', 10, TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime')
   ->Set($Explicit, $Drop);

$SQL->Select('c.CategoryID, c.CountDiscussions, c.Description, c.Sort, c.InsertUserID, c.UpdateUserID, c.DateInserted, c.DateUpdated')
   ->Select("' > ', p.Name, c.Name", 'concat_ws', 'Name')
   ->From('Category c')
   ->Join('Category p', 'c.ParentCategoryID = p.CategoryID', 'left')
   ->Where('c.AllowDiscussions', '1', TRUE, FALSE);
$Construct->View('vw_Category', $SQL);

if ($Drop)
   $SQL->Insert('Category', array('InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Format::ToDateTime(), 'DateUpdated' => Format::ToDateTime(), 'Name' => 'General', 'Description' => 'General discussions', 'Sort' => '1'));
   
// Construct the discussion table.
$Construct->Table('Discussion')
   ->Column('DiscussionID', 'int', 11, FALSE, NULL, 'primary', TRUE)
   ->Column('CategoryID', 'int', 4, FALSE, NULL, 'key')
   ->Column('InsertUserID', 'int', 10, FALSE, NULL, 'key')
   ->Column('UpdateUserID', 'int', 10, FALSE, NULL)
   ->Column('FirstCommentID', 'int', 11, TRUE, NULL, 'key')
   ->Column('LastCommentID', 'int', 11, TRUE, NULL, 'key')
   ->Column('Name', 'varchar', 100)
   ->Column('CountComments', 'int', 4, FALSE, '1')
   ->Column('Closed', array('1', '0'), '', FALSE, '0')
   ->Column('Announce', array('1', '0'), '', FALSE, '0')
   ->Column('Sink', array('1', '0'), '', FALSE, '0')
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime')
   ->Column('DateLastComment', 'datetime')
   ->Column('Attributes', 'text', '', TRUE)
   ->Set($Explicit, $Drop);
   
// Allows the tracking of relationships between discussions and users (bookmarks, dismissed announcements, # of read comments in a discussion, etc)
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->Table('UserDiscussion')
   ->Column('UserID', 'int', 11, FALSE, NULL, 'primary')
   ->Column('DiscussionID', 'int', 11, FALSE, NULL, 'primary')
   ->Column('CountComments', 'int', 4, FALSE, '0')
   ->Column('DateLastViewed', 'datetime')
   ->Column('Dismissed', 'varchar', 1, TRUE) // Relates to dismissed announcements
   ->Column('Bookmarked', 'varchar', 1, TRUE)
   ->Set($Explicit, $Drop);

$Construct->Table('Comment')
   ->Column('CommentID', 'int', 11, FALSE, NULL, 'primary', TRUE)
   ->Column('DiscussionID', 'int', 11, FALSE, NULL, 'key')
   ->Column('InsertUserID', 'int', 10, TRUE, NULL, 'key')
   ->Column('UpdateUserID', 'int', 10, TRUE)
   ->Column('DeleteUserID', 'int', 10, TRUE)
   ->Column('Body', 'text')
   ->Column('Format', 'varchar', 20, TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('DateDeleted', 'datetime', '', TRUE)
   ->Column('DateUpdated', 'datetime', '', TRUE)
   ->Column('Flag', 'int', 2, TRUE, 0)
   ->Set($Explicit, $Drop);

// Allows the tracking of already-read comments on a per-user basis.
$Construct->Table('CommentWatch')
   ->Column('UserID', 'int', 11, FALSE, NULL, 'primary')
   ->Column('CommentID', 'int', 11, FALSE, NULL, 'primary')
   ->Column('DateLastViewed', 'datetime')
   ->Set($Explicit, $Drop);
   
// Add extra columns to user table for tracking discussions & comments
$Construct->Table('User')
   ->Column('CountDiscussions', 'int', 11, FALSE, '0')
   ->Column('CountUnreadDiscussions', 'int', 11, FALSE, '0')
   ->Column('CountComments', 'int', 11, FALSE, '0')
   ->Column('CountDrafts', 'int', 11, FALSE, '0')
   ->Column('CountBookmarks', 'int', 11, FALSE, '0')
   ->Set();

$Construct->Table('Draft')
   ->Column('DraftID', 'int', 11, FALSE, NULL, 'primary', TRUE)
   ->Column('DiscussionID', 'int', 11, TRUE, NULL, 'key')
   ->Column('CategoryID', 'int', 4, TRUE, NULL, 'key')
   ->Column('InsertUserID', 'int', 10, FALSE, NULL, 'key')
   ->Column('UpdateUserID', 'int', 10, FALSE, NULL)
   ->Column('Name', 'varchar', 100, TRUE)
   ->Column('Closed', array('1', '0'), '', FALSE, '0')
   ->Column('Announce', array('1', '0'), '', FALSE, '0')
   ->Column('Sink', array('1', '0'), '', FALSE, '0')
   ->Column('Body', 'text')
   ->Column('Format', 'varchar', 20, TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime', '', TRUE)
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
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'DiscussionMention', 'FullHeadline' => '%1$s mentioned you in a %8$s.', 'ProfileHeadline' => '%1$s mentioned you in a %8$s.', 'RouteCode' => 'discussion', 'Notify' => '1', 'Public' => '0'));

// People mentioning others in comments
if ($SQL->GetWhere('ActivityType', array('Name' => 'CommentMention'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'CommentMention', 'FullHeadline' => '%1$s mentioned you in a %8$s.', 'ProfileHeadline' => '%1$s mentioned you in a %8$s.', 'RouteCode' => 'comment', 'Notify' => '1', 'Public' => '0'));
   
// Add the search types for Vanilla.
$Gdn_Search = Gdn::Factory('SearchModel');
if(!is_null($Gdn_Search)) {
   $Gdn_Search->AddTableType('Comment', 'Category');
}
unset($Gdn_Search);