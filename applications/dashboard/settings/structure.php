<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

if (!isset($Drop))
   $Drop = FALSE;
   
if (!isset($Explicit))
   $Explicit = TRUE;

$SQL = $Database->SQL();
$Construct = $Database->Structure();

// Role Table
$Construct->Table('Role');

$RoleTableExists = $Construct->TableExists();

$Construct
   ->PrimaryKey('RoleID')
   ->Column('Name', 'varchar(100)')
   ->Column('Description', 'varchar(500)', TRUE)
   ->Column('Sort', 'int', TRUE)
   ->Column('Deletable', 'tinyint(1)', '1')
   ->Column('CanSession', 'tinyint(1)', '1')
   ->Set($Explicit, $Drop);

if (!$RoleTableExists || $Drop) {
   // Define some roles.
   // Note that every RoleID must be a power of two so that they can be combined as a bit-mask.
   $RoleModel = Gdn::Factory('RoleModel');
   $RoleModel->Database = $Database;
   $RoleModel->SQL = $SQL;
   $RoleModel->Define(array('Name' => 'Banned', 'RoleID' => 1, 'Sort' => '1', 'Deletable' => '1', 'CanSession' => '0', 'Description' => 'Banned users are not allowed to participate or sign in.'));
   $RoleModel->Define(array('Name' => 'Guest', 'RoleID' => 2, 'Sort' => '2', 'Deletable' => '0', 'CanSession' => '0', 'Description' => 'Guests can only view content. Anyone browsing the site who is not signed in is considered to be a "Guest".'));
   $RoleModel->Define(array('Name' => 'Applicant', 'RoleID' => 4, 'Sort' => '3', 'Deletable' => '0', 'CanSession' => '0', 'Description' => 'Users who have applied for membership, but have not yet been accepted. They have the same permissions as guests.'));
   $RoleModel->Define(array('Name' => 'Member', 'RoleID' => 8, 'Sort' => '4', 'Deletable' => '1', 'CanSession' => '1', 'Description' => 'Members can participate in discussions.'));
   $RoleModel->Define(array('Name' => 'Moderator', 'RoleID' => 32, 'Sort' => '5', 'Deletable' => '1', 'CanSession' => '1', 'Description' => 'Moderators have permission to edit most content.'));
   $RoleModel->Define(array('Name' => 'Administrator', 'RoleID' => 16, 'Sort' => '6', 'Deletable' => '1', 'CanSession' => '1', 'Description' => 'Administrators have permission to do anything.'));
   $RoleModel->Define(array('Name' => 'Confirm Email', 'RoleID' => 3, 'Sort' => '7', 'Deletable' => '1', 'CanSession' => '0', 'Description' => 'Users must confirm their emails before becoming full members. They get assigned to this role.'));
   unset($RoleModel);
}

// User Table
$Construct->Table('User');

$PhotoIDExists = $Construct->ColumnExists('PhotoID');
$PhotoExists = $Construct->ColumnExists('Photo');

$Construct
	->PrimaryKey('UserID')
   ->Column('Name', 'varchar(20)', FALSE, 'key')
   ->Column('Password', 'varbinary(50)')
	->Column('HashMethod', 'varchar(10)', TRUE)
   ->Column('Photo', 'varchar(255)', NULL)
   ->Column('About', 'text', TRUE)
   ->Column('Email', 'varchar(200)')
   ->Column('ShowEmail', 'tinyint(1)', '0')
   ->Column('Gender', array('m', 'f'), 'm')
   ->Column('CountVisits', 'int', '0')
   ->Column('CountInvitations', 'int', '0')
   ->Column('CountNotifications', 'int', NULL)
   ->Column('InviteUserID', 'int', TRUE)
   ->Column('DiscoveryText', 'text', TRUE)
   ->Column('Preferences', 'text', TRUE)
   ->Column('Permissions', 'text', TRUE)
   ->Column('Attributes', 'text', TRUE)
   ->Column('DateSetInvitations', 'datetime', TRUE)
   ->Column('DateOfBirth', 'datetime', TRUE)
   ->Column('DateFirstVisit', 'datetime', TRUE)
   ->Column('DateLastActive', 'datetime', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('HourOffset', 'int', '0')
	->Column('Score', 'float', NULL)
   ->Column('Admin', 'tinyint(1)', '0')
   ->Column('Deleted', 'tinyint(1)', '0')
   ->Set($Explicit, $Drop);

// UserRole Table
$Construct->Table('UserRole');

$UserRoleExists = $Construct->TableExists();

$Construct
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('RoleID', 'int', FALSE, 'primary')
   ->Set($Explicit, $Drop);

if (!$UserRoleExists) {
   // Assign the guest user to the guest role
   $SQL->Replace('UserRole', array(), array('UserID' => 0, 'RoleID' => 2));
   // Assign the admin user to admin role
   $SQL->Replace('UserRole', array(), array('UserID' => 1, 'RoleID' => 16));
}

// User Meta Table
$Construct->Table('UserMeta')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('Name', 'varchar(255)', FALSE, 'primary')
   ->Column('Value', 'text', TRUE)
   ->Set($Explicit, $Drop);

// Create the authentication table.
$Construct->Table('UserAuthentication')
	->Column('ForeignUserKey', 'varchar(255)', FALSE, 'primary')
	->Column('ProviderKey', 'varchar(64)', FALSE, 'primary')
	->Column('UserID', 'int', FALSE, 'key')
	->Set($Explicit, $Drop);
	
$Construct->Table('UserAuthenticationProvider')
   ->Column('AuthenticationKey', 'varchar(64)', FALSE, 'primary')
   ->Column('AuthenticationSchemeAlias', 'varchar(32)', FALSE)
   ->Column('URL', 'varchar(255)', TRUE)
   ->Column('AssociationSecret', 'text', FALSE)
   ->Column('AssociationHashMethod', array('HMAC-SHA1','HMAC-PLAINTEXT'), FALSE)
   ->Column('AuthenticateUrl', 'varchar(255)', TRUE)
   ->Column('RegisterUrl', 'varchar(255)', TRUE)
   ->Column('SignInUrl', 'varchar(255)', TRUE)
   ->Column('SignOutUrl', 'varchar(255)', TRUE)
   ->Column('PasswordUrl', 'varchar(255)', TRUE)
   ->Column('ProfileUrl', 'varchar(255)', TRUE)
   ->Set($Explicit, $Drop);

$Construct->Table('UserAuthenticationNonce')
   ->Column('Nonce', 'varchar(200)', FALSE, 'primary')
   ->Column('Token', 'varchar(128)', FALSE)
   ->Column('Timestamp', 'timestamp', FALSE)
   ->Set($Explicit, $Drop);

$Construct->Table('UserAuthenticationToken')
   ->Column('Token', 'varchar(128)', FALSE, 'primary')
   ->Column('ProviderKey', 'varchar(64)', FALSE, 'primary')
   ->Column('ForeignUserKey', 'varchar(255)', TRUE)
   ->Column('TokenSecret', 'varchar(64)', FALSE)
   ->Column('TokenType', array('request', 'access'), FALSE)
   ->Column('Authorized', 'tinyint(1)', FALSE)
   ->Column('Timestamp', 'timestamp', FALSE)
   ->Column('Lifetime', 'int', FALSE)
   ->Set($Explicit, $Drop);

// Only Create the permission table if we are using Garden's permission model.
$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Database = $Database;
$PermissionModel->SQL = $SQL;
if($PermissionModel instanceof PermissionModel) {
	// Permission Table
	$Construct->Table('Permission')
		->PrimaryKey('PermissionID')
		->Column('RoleID', 'int', 0, 'key')
		->Column('JunctionTable', 'varchar(100)', TRUE) 
		->Column('JunctionColumn', 'varchar(100)', TRUE)
		->Column('JunctionID', 'int', TRUE)
		// The actual permissions will be added by PermissionModel::Define()
		->Set($Explicit, $Drop);
}
   
// Define the set of permissions that garden uses.
$PermissionModel->Define(array(
	'Garden.Email.Manage',
	'Garden.Settings.Manage',
	'Garden.Routes.Manage',
   'Garden.Messages.Manage',
	'Garden.Applications.Manage',
	'Garden.Plugins.Manage',
	'Garden.Themes.Manage',
	'Garden.SignIn.Allow',
	'Garden.Registration.Manage',
	'Garden.Applicants.Manage',
	'Garden.Roles.Manage',
	'Garden.Users.Add',
	'Garden.Users.Edit',
	'Garden.Users.Delete',
	'Garden.Users.Approve',
	'Garden.Activity.Delete',
	'Garden.Activity.View' => 1,
	'Garden.Profiles.View' => 1
	));
	
// Set initial guest permissions.
$PermissionModel->Save(array(
	'RoleID' => 2,
	'Garden.Activity.View' => 1,
	'Garden.Profiles.View' => 1
	));
	
// Set initial applicant permissions.
$PermissionModel->Save(array(
	'RoleID' => 4,
	'Garden.Activity.View' => 1,
	'Garden.Profiles.View' => 1
	));

// Set initial member permissions.
$PermissionModel->Save(array(
	'RoleID' => 8,
	'Garden.SignIn.Allow' => 1,
	'Garden.Activity.View' => 1,
	'Garden.Profiles.View' => 1
	));

// Set initial moderator permissions.
$PermissionModel->Save(array(
	'RoleID' => 32,
	'Garden.SignIn.Allow' => 1,
	'Garden.Activity.View' => 1,
	'Garden.Profiles.View' => 1
	));

// Set initial admininstrator permissions.
$PermissionModel->Save(array(
	'RoleID' => 16,
	'Garden.Settings.Manage' => 1,
	'Garden.Routes.Manage' => 1,
	'Garden.Applications.Manage' => 1,
	'Garden.Plugins.Manage' => 1,
	'Garden.Themes.Manage' => 1,
	'Garden.SignIn.Allow' => 1,
	'Garden.Registration.Manage' => 1,
	'Garden.Applicants.Manage' => 1,
	'Garden.Roles.Manage' => 1,
	'Garden.Users.Add' => 1,
	'Garden.Users.Edit' => 1,
	'Garden.Users.Delete' => 1,
	'Garden.Users.Approve' => 1,
	'Garden.Activity.Delete' => 1,
	'Garden.Activity.View' => 1,
   'Garden.Profiles.View' => 1
	));

// Photo Table
$Construct->Table('Photo');

$PhotoTableExists = $Construct->TableExists('Photo');

$Construct
	->PrimaryKey('PhotoID')
   ->Column('Name', 'varchar(255)')
   ->Column('InsertUserID', 'int', TRUE, 'key')
   ->Column('DateInserted', 'datetime')
   ->Set($Explicit, $Drop);

// Invitation Table
$Construct->Table('Invitation')
	->PrimaryKey('InvitationID')
   ->Column('Email', 'varchar(200)')
   ->Column('Code', 'varchar(50)')
   ->Column('InsertUserID', 'int', TRUE, 'key')
   ->Column('DateInserted', 'datetime')
   ->Column('AcceptedUserID', 'int', TRUE)
   ->Set($Explicit, $Drop);

// ActivityType Table
$Construct->Table('ActivityType')
	->PrimaryKey('ActivityTypeID')
   ->Column('Name', 'varchar(20)')
   ->Column('AllowComments', 'tinyint(1)', '0')
   ->Column('ShowIcon', 'tinyint(1)', '0')
   ->Column('ProfileHeadline', 'varchar(255)')
   ->Column('FullHeadline', 'varchar(255)')
   ->Column('RouteCode', 'varchar(255)', TRUE)
   ->Column('Notify', 'tinyint(1)', '0') // Add to RegardingUserID's notification list?
   ->Column('Public', 'tinyint(1)', '1') // Should everyone be able to see this, or just the RegardingUserID?
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
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'JoinApproved', 'FullHeadline' => '%1$s approved %4$s membership application.', 'ProfileHeadline' => '%1$s approved %4$s membership application.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'JoinCreated'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'JoinCreated', 'FullHeadline' => '%3$s created an account for %1$s.', 'ProfileHeadline' => '%3$s created an account for %1$s.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'AboutUpdate'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'AboutUpdate', 'FullHeadline' => '%1$s updated %6$s profile.', 'ProfileHeadline' => '%1$s updated %6$s profile.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'WallComment'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'ShowIcon' => '1', 'Name' => 'WallComment', 'FullHeadline' => '%1$s wrote on %4$s %5$s.', 'ProfileHeadline' => '%1$s wrote:')); 
if ($SQL->GetWhere('ActivityType', array('Name' => 'PictureChange'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'PictureChange', 'FullHeadline' => '%1$s changed %6$s profile picture.', 'ProfileHeadline' => '%1$s changed %6$s profile picture.'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'RoleChange'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'RoleChange', 'FullHeadline' => '%1$s changed %4$s permissions.', 'ProfileHeadline' => '%1$s changed %4$s permissions.', 'Notify' => '1'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'ActivityComment'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'ShowIcon' => '1', 'Name' => 'ActivityComment', 'FullHeadline' => '%1$s commented on %4$s %8$s.', 'ProfileHeadline' => '%1$s', 'RouteCode' => 'activity', 'Notify' => '1'));
if ($SQL->GetWhere('ActivityType', array('Name' => 'Import'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'Import', 'FullHeadline' => '%1$s imported data.', 'ProfileHeadline' => '%1$s imported data.', 'Notify' => '1', 'Public' => '0'));

// Activity Table
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->Table('Activity')
	->PrimaryKey('ActivityID')
   ->Column('CommentActivityID', 'int', TRUE, 'key')
   ->Column('ActivityTypeID', 'int')
   ->Column('ActivityUserID', 'int', TRUE, 'key')
   ->Column('RegardingUserID', 'int', TRUE)
   ->Column('Story', 'text', TRUE)
   ->Column('Route', 'varchar(255)', TRUE)
   ->Column('CountComments', 'int', '0')
   ->Column('InsertUserID', 'int', TRUE, 'key')
   ->Column('DateInserted', 'datetime')
   ->Set($Explicit, $Drop);

// Message Table
$Construct->Table('Message')
	->PrimaryKey('MessageID')
   ->Column('Content', 'text')
   ->Column('Format', 'varchar(20)', TRUE)
   ->Column('AllowDismiss', 'tinyint(1)', '1')
   ->Column('Enabled', 'tinyint(1)', '1')
   ->Column('Application', 'varchar(255)', TRUE)
   ->Column('Controller', 'varchar(255)', TRUE)
   ->Column('Method', 'varchar(255)', TRUE)
   ->Column('AssetTarget', 'varchar(20)', TRUE)
	->Column('CssClass', 'varchar(20)', TRUE)
   ->Column('Sort', 'int', TRUE)
   ->Set($Explicit, $Drop);

$Prefix = $SQL->Database->DatabasePrefix;

if ($PhotoIDExists && !$PhotoExists) {
   $Construct->Query("update {$Prefix}User u
   join {$Prefix}Photo p
      on u.PhotoID = p.PhotoID
   set u.Photo = p.Name");
}

if ($PhotoIDExists) {
   $Construct->Table('User')->DropColumn('PhotoID');
}

$Construct->Table('Tag')
	->PrimaryKey('TagID')
   ->Column('Name', 'varchar(255)', 'unique')
   ->Column('InsertUserID', 'int', TRUE, 'key')
   ->Column('DateInserted', 'datetime')
   ->Engine('InnoDB')
   ->Set($Explicit, $Drop);
