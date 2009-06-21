<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

if (!isset($Drop))
   $Drop = FALSE;
   
if (!isset($Explicit))
   $Explicit = TRUE;
	
$Construct = Gdn::Structure();
   
// Permission Table
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->Table('Permission')
   ->Column('PermissionID', 'int', 4, FALSE, NULL, 'primary', TRUE)
   ->Column('Name', 'varchar', 200)
   // Used for joining permissions to separate tables.
   // ie. Specific permissions for categories:
   // select c.*
   // from Category c
   // join RolePermission rp
   //    on c.CategoryID = rp.JunctionID
   // join Permission p
   //    on rp.PermissionID = p.PermissionID
   //    and p.JunctionTable = 'Category' // <-- There's actually no need for this part of the query since the permission will only ever be applied to categories based on separate business rules on the edit role screen.
   // where p.Name in ('List of permission names')
   ->Column('JunctionTable', 'varchar', 100, TRUE) 
   ->Column('JunctionColumn', 'varchar', 100, TRUE) 
   ->Set($Explicit, $Drop);

// Role Table
$Construct->Table('Role')
   ->Column('RoleID', 'int', 2, FALSE, NULL, 'primary', TRUE)
   ->Column('Name', 'varchar', 100)
   ->Column('Description', 'varchar', 200, TRUE)
   ->Column('Sort', 'int', 2, TRUE)
   ->Column('Deletable', array('1', '0'), '', FALSE, '1')
   ->Column('CanSession', array('1', '0'), '', FALSE, '1')
   ->Set($Explicit, $Drop);

// Insert some permissions for the Garden setup
if (!is_object($Validation))
   $Validation = new Validation();
   
$PermissionModel = new PermissionModel($Validation);
$Permissions = array();
$Permissions[] = 'Garden.Settings.Manage';
$Permissions[] = 'Garden.Email.Manage';
$Permissions[] = 'Garden.Routes.Manage';
$Permissions[] = 'Garden.Applications.Manage';
$Permissions[] = 'Garden.Plugins.Manage';
$Permissions[] = 'Garden.Themes.Manage';
$Permissions[] = 'Garden.SignIn.Allow';
$Permissions[] = 'Garden.Registration.Manage';
$Permissions[] = 'Garden.Applicants.Manage';
$Permissions[] = 'Garden.Roles.Manage';
$Permissions[] = 'Garden.Users.Add';
$Permissions[] = 'Garden.Users.Edit';
$Permissions[] = 'Garden.Users.Delete';
$Permissions[] = 'Garden.Users.Approve';
$Permissions[] = 'Garden.Activity.Delete';
$PermissionModel->InsertNew($Permissions);

// Insert some roles
$SQL = $Database->SQL();
if ($SQL->GetWhere('Role', array('Name' => 'Banned'))->NumRows() == 0)
   $SQL->Insert('Role', array('Sort' => '1', 'Deletable' => '1', 'CanSession' => '1', 'Name' => 'Banned', 'Description' => 'Ex-members who do not have permission to sign in.'));
if ($SQL->GetWhere('Role', array('Name' => 'Guest'))->NumRows() == 0)
   $SQL->Insert('Role', array('Sort' => '2', 'Deletable' => '0', 'CanSession' => '0', 'Name' => 'Guest', 'Description' => 'Users who are not authenticated in any way. Absolutely no permissions to do anything because they have no user account.'));
if ($SQL->GetWhere('Role', array('Name' => 'Applicant'))->NumRows() == 0)
   $SQL->Insert('Role', array('Sort' => '3', 'Deletable' => '0', 'CanSession' => '0', 'Name' => 'Applicant', 'Description' => 'Users who have applied for membership. They do not have permission to sign in.'));
if ($SQL->GetWhere('Role', array('Name' => 'Member'))->NumRows() == 0)
   $SQL->Insert('Role', array('Sort' => '4', 'Deletable' => '1', 'CanSession' => '1', 'Name' => 'Member', 'Description' => 'Members can perform rudimentary operations. They have no control over the application or other members.'));
if ($SQL->GetWhere('Role', array('Name' => 'Administrator'))->NumRows() == 0)
   $SQL->Insert('Role', array('Sort' => '5', 'Deletable' => '1', 'CanSession' => '1', 'Name' => 'Administrator', 'Description' => 'Administrators have access to everything in the application.'));

// RolePermission Table
$Construct->Table('RolePermission')
   ->Column('RoleID', 'int', 2, FALSE, NULL, 'primary')
   ->Column('PermissionID', 'int', 4, FALSE, NULL, 'primary')
   // The table that this JunctionID relates to is based on the value in
   // Permission.JunctionTable. JunctionID should relate to the primary key of
   // JunctionTable.
   ->Column('JunctionID', 'int', 11, TRUE, NULL, 'primary') 
   ->Set($Explicit, $Drop);

// Insert some permissions for Garden
// Member permissions
$PermissionID = $SQL->Select('PermissionID')
   ->From('Permission')
   ->Where('Name', 'Garden.SignIn.Allow')
   ->Get()
   ->FirstRow()
   ->PermissionID;
if ($SQL->GetWhere('RolePermission', array('RoleID' => '4', 'PermissionID' => $PermissionID))->NumRows() == 0)
   $SQL->Insert('RolePermission', array('RoleID' => '4', 'PermissionID' => $PermissionID));
   
// Admin permissions
$SQL->Delete('RolePermission', array('RoleID' => '5'));
$Select = $SQL->Select('5, PermissionID')
   ->From('Permission')
   ->GetSelect();
$SQL->Insert('RolePermission', array('RoleID', 'PermissionID'), $Select);

// User Table
$Construct->Table('User')
   ->Column('UserID', 'int', 10, FALSE, NULL, 'primary', TRUE)
   ->Column('PhotoID', 'int', 8, TRUE, NULL, 'key')
   ->Column('Name', 'varchar', 20, FALSE, NULL, 'key')
   ->Column('Password', 'varbinary', 34)
   ->Column('About', 'text', '', TRUE)
   ->Column('Email', 'varchar', 200)
   ->Column('ShowEmail', array('1', '0'), '', FALSE, '0')
   ->Column('Gender', array('m', 'f'), '', FALSE, 'm')
   ->Column('CountVisits', 'int', 8, FALSE, '0')
   ->Column('CountInvitations', 'int', 2, FALSE, '0')
   ->Column('CountNotifications', 'int', 11, FALSE, '0')
   ->Column('InviteUserID', 'int', 10, TRUE)
   ->Column('DiscoveryText', 'text', '', TRUE)
   ->Column('Preferences', 'text', '', TRUE)
   ->Column('Permissions', 'text', '', TRUE)
   ->Column('Attributes', 'text', '', TRUE)
   ->Column('DateSetInvitations', 'datetime', '', TRUE)
   ->Column('DateOfBirth', 'datetime', '', TRUE)
   ->Column('DateFirstVisit', 'datetime', '', TRUE)
   ->Column('DateLastActive', 'datetime', '', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime', '', TRUE)
   ->Column('HourOffset', 'int', 2, FALSE, '0')
   ->Column('Admin', array('1', '0'), '', FALSE, '0')
   ->Set($Explicit, $Drop);

// UserRole Table
$Construct->Table('UserRole')
   ->Column('UserID', 'int', 10, FALSE, NULL, 'primary')
   ->Column('RoleID', 'int', 2, FALSE, NULL, 'primary')
   ->Set($Explicit, $Drop);
   
// Assign the guest user to the guest role 
if ($SQL->GetWhere('UserRole', array('UserID' => '0'))->NumRows() == 0)
   $SQL->Insert('UserRole', array('UserID' => 0, 'RoleID' => 2));

// Photo Table
$Construct->Table('Photo')
   ->Column('PhotoID', 'int', 8, FALSE, NULL, 'primary', TRUE)
   ->Column('Name', 'varchar', 255)
   ->Column('InsertUserID', 'int', 10, TRUE, NULL, 'key')
   ->Column('DateInserted', 'datetime')
   ->Set($Explicit, $Drop);

// UserPhoto Table
$Construct->Table('UserPhoto')
   ->Column('UserID', 'int', 10, FALSE, NULL, 'primary')
   ->Column('PhotoID', 'int', 8, FALSE, NULL, 'primary')
   ->Set($Explicit, $Drop);

// Invitation Table
$Construct->Table('Invitation')
   ->Column('InvitationID', 'int', 10, FALSE, NULL, 'primary', TRUE)
   ->Column('Email', 'varchar', 200)
   ->Column('Code', 'varchar', 50)
   ->Column('InsertUserID', 'int', 10, TRUE, NULL, 'key')
   ->Column('DateInserted', 'datetime')
   ->Column('AcceptedUserID', 'int', 10, TRUE)
   ->Set($Explicit, $Drop);

// ActivityType Table
$Construct->Table('ActivityType')
   ->Column('ActivityTypeID', 'int', 3, FALSE, NULL, 'primary', TRUE)
   ->Column('Name', 'varchar', 20)
   ->Column('AllowComments', array('1', '0'), '', FALSE, '0')
   ->Column('ShowIcon', array('1', '0'), '', FALSE, '0')
   ->Column('ProfileHeadline', 'varchar', 255)
   ->Column('FullHeadline', 'varchar', 255)
   ->Column('RouteCode', 'varchar', 255, TRUE)
   ->Column('Notify', array('1', '0'), '', FALSE, '0') // Add to RegardingUserID's notification list?
   ->Column('Public', array('1', '0'), '', FALSE, '1') // Should everyone be able to see this, or just the RegardingUserID?
   ->Set($Explicit, $Drop);

// Insert some activity types
///  %1 = ActivityName
///  %2 = ActivityName Possessive: Username
///  %3 = RegardingName
///  %4 = RegardingName Possessive: Username, his, her, your
///  %5 = Link to RegardingName's Wall
///  %6 = his/her
///  %7 = he/she
///  %8 = RouteCode & Route
if ($SQL->GetWhere('ActivityType', array('Name' => 'SignIn'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'SignIn', 'FullHeadline' => '%1$s signed in.', 'ProfileHeadline' => '%1$s signed in.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'Join'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'Join', 'FullHeadline' => '%1$s joined.', 'ProfileHeadline' => '%1$s joined.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'JoinInvite'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'JoinInvite', 'FullHeadline' => '%1$s accepted %4$s invitation for membership.', 'ProfileHeadline' => '%1$s accepted %4$s invitation for membership.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'JoinApproved'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'JoinApproved', 'FullHeadline' => '%3$s approved %2$s membership application.', 'ProfileHeadline' => '%3$s approved %2$s membership application.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'JoinCreated'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'JoinCreated', 'FullHeadline' => '%3$s created an account for %1$s.', 'ProfileHeadline' => '%3$s created an account for %1$s.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'AboutUpdate'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'AboutUpdate', 'FullHeadline' => '%1$s updated %6$s profile.', 'ProfileHeadline' => '%1$s updated %6$s profile.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'WallComment'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'ShowIcon' => '1', 'Name' => 'WallComment', 'FullHeadline' => '%1$s wrote on %4$s %5$s.', 'ProfileHeadline' => '%1$s wrote:')); 
if ($SQL->GetWhere('ActivityType', array('Name' => 'PictureChange'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'PictureChange', 'FullHeadline' => '%1$s changed %6$s profile picture.', 'ProfileHeadline' => '%1$s changed %6$s profile picture.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'RoleChange'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'RoleChange', 'FullHeadline' => '%1$s changed %4$s permissions.', 'ProfileHeadline' => '%1$s changed %4$s permissions.', 'Notify' => '1'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'ActivityComment'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'ShowIcon' => '1', 'Name' => 'ActivityComment', 'FullHeadline' => '%1$s commented on %4$s %8$s.', 'ProfileHeadline' => '%1$s', 'RouteCode' => 'activity', 'Notify' => '1')); 

// Activity Table
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->Table('Activity')
   ->Column('ActivityID', 'int', 11, FALSE, NULL, 'primary', TRUE)
   ->Column('CommentActivityID', 'int', 11, TRUE, NULL, 'key')
   ->Column('ActivityTypeID', 'int', 3)
   ->Column('ActivityUserID', 'int', 10, TRUE, NULL, 'key')
   ->Column('RegardingUserID', 'int', 10, TRUE)
   ->Column('Story', 'text', '', TRUE)
   ->Column('Route', 'varchar', 255, TRUE)
   ->Column('CountComments', 'int', 10, FALSE, '0')
   ->Column('InsertUserID', 'int', 10, TRUE, NULL, 'key')
   ->Column('DateInserted', 'datetime')
   ->Set($Explicit, $Drop);
	
// Search Document Type Table
$Construct->Table('TableType')
	->Column('TableName', 'varchar', 50, FALSE, NULL, 'primary')
	->Column('PermissionTableName', 'varchar', 50, TRUE)
	->Set($Explicit, $Drop);
	
// Search Document Table
$Construct->Table('SearchDocument')
	->Column('DocumentID', 'int', 11, FALSE, NULL, 'primary', TRUE)
	->Column('TableName', 'varchar', 50)
	->Column('PrimaryID', 'int', 11, FALSE)
	->Column('PermissionJunctionID', 'int', 11, TRUE)
	->Column('Title', 'varchar', 50, FALSE)
	->Column('Summary', 'varchar', 200, FALSE)
	->Column('Url', 'varchar', 255, FALSE)
	->Column('InsertUserID', 'int', 10, FALSE)
	->Column('DateInserted', 'datetime', FALSE)
	->Set($Explicit, $Drop);
	
// Search Index Table
$Construct->Table('SearchKeyword')
	->Column('KeywordID', 'int', 11, FALSE, NULL, 'primary', TRUE)
	->Column('Keyword', 'varchar', 50, FALSE, FALSE, 'key')
	->Set($Explicit, $Drop);
	
// Search Index to Document table.
$Construct->Table('SearchKeywordDocument')
	->Column('KeywordID', 'int', 11, FALSE, NULL, 'primary')
	->Column('DocumentID', 'int', 11, FALSE, NULL, 'primary')
	->Set($Explicit, $Drop);

// vw_SingleRoleUser Returns all UserIDs that have only one role.
$SQL->Select('UserID')
   ->From('UserRole')
   ->GroupBy('UserID')
   ->Having('count(RoleID) =', '1', TRUE, FALSE);
$Construct->View('vw_SingleRoleUser', $SQL);

// vw_ApplicantID Returns all UserIDs in the applicant role.
$SQL->Select('User.UserID')
   ->From('User')
   ->Join('UserRole', 'User.UserID = UserRole.UserID')
   ->Where('UserRole.RoleID', '3', TRUE, FALSE) // 3 is Applicant RoleID
   ->GroupBy('UserID');
$Construct->View('vw_ApplicantID', $SQL);

// vw_Applicant Returns all users in the applicant role.
$SQL->Select('User.*')
   ->From('User')
   ->Join('vw_ApplicantID', 'User.UserID = vw_ApplicantID.UserID');
$Construct->View('vw_Applicant', $SQL);

// vw_RolePermission
$SQL->Select('rp.*')
   ->Select('p.Name', '', 'Permission')
   ->From('RolePermission rp')
   ->Join('Permission p', 'rp.PermissionID = p.PermissionID');
$Construct->View('vw_RolePermission', $SQL);