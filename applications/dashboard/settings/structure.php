<?php if (!defined('APPLICATION')) {
    exit();
      }
/**
 * Dashboard database structure.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

if (!isset($Drop)) {
    $Drop = false;
}

if (!isset($Explicit)) {
    $Explicit = true;
}

$Database = Gdn::Database();
$SQL = $Database->SQL();
$Construct = $Database->Structure();
$Px = $Database->DatabasePrefix;

// Role Table
$Construct->Table('Role');

$RoleTableExists = $Construct->TableExists();

$Construct
    ->PrimaryKey('RoleID')
    ->Column('Name', 'varchar(100)')
    ->Column('Description', 'varchar(500)', true)
    ->Column('Type', array(RoleModel::TYPE_GUEST, RoleModel::TYPE_UNCONFIRMED, RoleModel::TYPE_APPLICANT, RoleModel::TYPE_MEMBER, RoleModel::TYPE_MODERATOR, RoleModel::TYPE_ADMINISTRATOR), true)
    ->Column('Sort', 'int', true)
    ->Column('Deletable', 'tinyint(1)', '1')
    ->Column('CanSession', 'tinyint(1)', '1')
    ->Column('PersonalInfo', 'tinyint(1)', '0')
    ->Set($Explicit, $Drop);

$RoleModel = new RoleModel();

if (!$RoleTableExists || $Drop) {
    // Define default roles.
    $RoleModel->Database = $Database;
    $RoleModel->SQL = $SQL;
    $Sort = 1;
    $RoleModel->Define(array('Name' => 'Guest', 'Type' => RoleModel::TYPE_GUEST, 'RoleID' => 2, 'Sort' => $Sort++, 'Deletable' => '0', 'CanSession' => '0', 'Description' => T('Guest Role Description', 'Guests can only view content. Anyone browsing the site who is not signed in is considered to be a "Guest".')));
    $RoleModel->Define(array('Name' => 'Unconfirmed', 'Type' => RoleModel::TYPE_UNCONFIRMED, 'RoleID' => 3, 'Sort' => $Sort++, 'Deletable' => '0', 'CanSession' => '1', 'Description' => T('Unconfirmed Role Description', 'Users must confirm their emails before becoming full members. They get assigned to this role.')));
    $RoleModel->Define(array('Name' => 'Applicant', 'Type' => RoleModel::TYPE_APPLICANT, 'RoleID' => 4, 'Sort' => $Sort++, 'Deletable' => '0', 'CanSession' => '1', 'Description' => T('Applicant Role Description', 'Users who have applied for membership, but have not yet been accepted. They have the same permissions as guests.')));
    $RoleModel->Define(array('Name' => 'Member', 'Type' => RoleModel::TYPE_MEMBER, 'RoleID' => 8, 'Sort' => $Sort++, 'Deletable' => '1', 'CanSession' => '1', 'Description' => T('Member Role Description', 'Members can participate in discussions.')));
    $RoleModel->Define(array('Name' => 'Moderator', 'Type' => RoleModel::TYPE_MODERATOR, 'RoleID' => 32, 'Sort' => $Sort++, 'Deletable' => '1', 'CanSession' => '1', 'Description' => T('Moderator Role Description', 'Moderators have permission to edit most content.')));
    $RoleModel->Define(array('Name' => 'Administrator', 'Type' => RoleModel::TYPE_ADMINISTRATOR, 'RoleID' => 16, 'Sort' => $Sort++, 'Deletable' => '1', 'CanSession' => '1', 'Description' => T('Administrator Role Description', 'Administrators have permission to do anything.')));
}

// User Table
$Construct->Table('User');

$PhotoIDExists = $Construct->ColumnExists('PhotoID');
$PhotoExists = $Construct->ColumnExists('Photo');
$UserExists = $Construct->TableExists();
$ConfirmedExists = $Construct->ColumnExists('Confirmed');

$Construct
    ->PrimaryKey('UserID')
    ->Column('Name', 'varchar(50)', false, 'key')
    ->Column('Password', 'varbinary(100)')// keep this longer because of some imports.
    ->Column('HashMethod', 'varchar(10)', true)
    ->Column('Photo', 'varchar(255)', null)
    ->Column('Title', 'varchar(100)', null)
    ->Column('Location', 'varchar(100)', null)
    ->Column('About', 'text', true)
    ->Column('Email', 'varchar(100)', false, 'index')
    ->Column('ShowEmail', 'tinyint(1)', '0')
    ->Column('Gender', array('u', 'm', 'f'), 'u')
    ->Column('CountVisits', 'int', '0')
    ->Column('CountInvitations', 'int', '0')
    ->Column('CountNotifications', 'int', null)
    ->Column('InviteUserID', 'int', true)
    ->Column('DiscoveryText', 'text', true)
    ->Column('Preferences', 'text', true)
    ->Column('Permissions', 'text', true)
    ->Column('Attributes', 'text', true)
    ->Column('DateSetInvitations', 'datetime', true)
    ->Column('DateOfBirth', 'datetime', true)
    ->Column('DateFirstVisit', 'datetime', true)
    ->Column('DateLastActive', 'datetime', true, 'index')
    ->Column('LastIPAddress', 'varchar(15)', true)
    ->Column('AllIPAddresses', 'varchar(100)', true)
    ->Column('DateInserted', 'datetime', false, 'index')
    ->Column('InsertIPAddress', 'varchar(15)', true)
    ->Column('DateUpdated', 'datetime', true)
    ->Column('UpdateIPAddress', 'varchar(15)', true)
    ->Column('HourOffset', 'int', '0')
    ->Column('Score', 'float', null)
    ->Column('Admin', 'tinyint(1)', '0')
    ->Column('Confirmed', 'tinyint(1)', '1')// 1 means email confirmed, otherwise not confirmed
    ->Column('Verified', 'tinyint(1)', '0')// 1 means verified (non spammer), otherwise not verified
    ->Column('Banned', 'tinyint(1)', '0')// 1 means banned, otherwise not banned
    ->Column('Deleted', 'tinyint(1)', '0')
    ->Column('Points', 'int', 0)
    ->Set($Explicit, $Drop);

// Modify all users with ConfirmEmail role to be unconfirmed
if ($UserExists && !$ConfirmedExists) {
    $ConfirmEmail = C('Garden.Registration.ConfirmEmail', false);
    $ConfirmEmailRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);
    if ($ConfirmEmail && !empty($ConfirmEmailRoleID)) {
        // Select unconfirmed users
        $Users = Gdn::SQL()->Select('UserID')->From('UserRole')->Where('RoleID', $ConfirmEmailRoleID)->Get();
        $UserIDs = array();
        while ($User = $Users->NextRow(DATASET_TYPE_ARRAY)) {
            $UserIDs[] = $User['UserID'];
        }

        // Update
        Gdn::SQL()->Update('User')->Set('Confirmed', 0)->WhereIn('UserID', $UserIDs)->Put();
        Gdn::SQL()->Delete('UserRole', array('RoleID' => $ConfirmEmailRoleID, 'UserID' => $UserIDs));
    }
}

// Make sure the system user is okay.
$SystemUserID = C('Garden.SystemUserID');
if ($SystemUserID) {
    $SysUser = Gdn::UserModel()->GetID($SystemUserID);

    if (!$SysUser || GetValue('Deleted', $SysUser) || GetValue('Admin', $SysUser) != 2) {
        $SystemUserID = false;
        RemoveFromConfig('Garden.SystemUserID');
    }
}

if (!$SystemUserID) {
    // Try and find a system user.
    $SystemUserID = Gdn::SQL()->GetWhere('User', array('Name' => 'System', 'Admin' => 2))->Value('UserID');
    if ($SystemUserID) {
        SaveToConfig('Garden.SystemUserID', $SystemUserID);
    } else {
        // Create a new one if we couldn't find one.
        Gdn::UserModel()->GetSystemUserID();
    }
}

// UserRole Table
$Construct->Table('UserRole');

$UserRoleExists = $Construct->TableExists();

$Construct
    ->Column('UserID', 'int', false, 'primary')
    ->Column('RoleID', 'int', false, array('primary', 'index'))
    ->Set($Explicit, $Drop);

// Fix old default roles that were stored in the config and user-role table.
if ($RoleTableExists && $UserRoleExists) {
    $types = $RoleModel->getAllDefaultRoles();
    if ($v = C('Garden.Registration.ApplicantRoleID')) {
        $SQL->Update('Role')
            ->Set('Type', RoleModel::TYPE_APPLICANT)
            ->Where('RoleID', $types[RoleModel::TYPE_APPLICANT])
            ->Put();
//      RemoveFromConfig('Garden.Registration.ApplicantRoleID');
    }

    if ($v = C('Garden.Registration.DefaultRoles')) {
        $SQL->Update('Role')
            ->Set('Type', RoleModel::TYPE_MEMBER)
            ->Where('RoleID', $types[RoleModel::TYPE_MEMBER])
            ->Put();
//      RemoveFromConfig('Garden.Registration.DefaultRoles');
    }

    if ($v = C('Garden.Registration.ConfirmEmailRole')) {
        $SQL->Update('Role')
            ->Set('Type', RoleModel::TYPE_UNCONFIRMED)
            ->Where('RoleID', $types[RoleModel::TYPE_UNCONFIRMED])
            ->Put();
//      RemoveFromConfig('Garden.Registration.ConfirmEmailRole');
    }

    $guestRoleIDs = Gdn::SQL()->GetWhere('UserRole', array('UserID' => 0))->ResultArray();
    if (!empty($guestRoleIDs)) {
        $SQL->Update('Role')
            ->Set('Type', RoleModel::TYPE_GUEST)
            ->Where('RoleID', $types[RoleModel::TYPE_GUEST])
            ->Put();

        $SQL->Delete('UserRole', array('UserID' => 0));
    }
}

if (!$UserRoleExists) {
    // Assign the admin user to admin role.
    $adminRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_ADMINISTRATOR);

    foreach ($adminRoleIDs as $id) {
        $SQL->Replace('UserRole', array(), array('UserID' => 1, 'RoleID' => $id));
    }
}

// User Meta Table
$Construct->Table('UserMeta')
    ->Column('UserID', 'int', false, 'primary')
    ->Column('Name', 'varchar(100)', false, array('primary', 'index'))
    ->Column('Value', 'text', true)
    ->Set($Explicit, $Drop);

// User Points Table
$Construct->Table('UserPoints')
    ->Column('SlotType', array('d', 'w', 'm', 'y', 'a'), false, 'primary')
    ->Column('TimeSlot', 'datetime', false, 'primary')
    ->Column('Source', 'varchar(10)', 'Total', 'primary')
    ->Column('CategoryID', 'int', 0, 'primary')
    ->Column('UserID', 'int', false, 'primary')
    ->Column('Points', 'int', 0)
    ->Set($Explicit, $Drop);

// Create the authentication table.
$Construct->Table('UserAuthentication')
    ->Column('ForeignUserKey', 'varchar(100)', false, 'primary')
    ->Column('ProviderKey', 'varchar(64)', false, 'primary')
    ->Column('UserID', 'int', false, 'key')
    ->Set($Explicit, $Drop);

$Construct->Table('UserAuthenticationProvider')
    ->Column('AuthenticationKey', 'varchar(64)', false, 'primary')
    ->Column('AuthenticationSchemeAlias', 'varchar(32)', false)
    ->Column('Name', 'varchar(50)', true)
    ->Column('URL', 'varchar(255)', true)
    ->Column('AssociationSecret', 'text', true)
    ->Column('AssociationHashMethod', 'varchar(20)', true)
    ->Column('AuthenticateUrl', 'varchar(255)', true)
    ->Column('RegisterUrl', 'varchar(255)', true)
    ->Column('SignInUrl', 'varchar(255)', true)
    ->Column('SignOutUrl', 'varchar(255)', true)
    ->Column('PasswordUrl', 'varchar(255)', true)
    ->Column('ProfileUrl', 'varchar(255)', true)
    ->Column('Attributes', 'text', true)
    ->Column('Active', 'tinyint', '1')
    ->Column('IsDefault', 'tinyint', 0)
    ->Set($Explicit, $Drop);

$Construct->Table('UserAuthenticationNonce')
    ->Column('Nonce', 'varchar(100)', false, 'primary')
    ->Column('Token', 'varchar(128)', false)
    ->Column('Timestamp', 'timestamp', false)
    ->Set($Explicit, $Drop);

$Construct->Table('UserAuthenticationToken')
    ->Column('Token', 'varchar(128)', false, 'primary')
    ->Column('ProviderKey', 'varchar(64)', false, 'primary')
    ->Column('ForeignUserKey', 'varchar(255)', true)
    ->Column('TokenSecret', 'varchar(64)', false)
    ->Column('TokenType', array('request', 'access'), false)
    ->Column('Authorized', 'tinyint(1)', false)
    ->Column('Timestamp', 'timestamp', false)
    ->Column('Lifetime', 'int', false)
    ->Set($Explicit, $Drop);

// Fix the sync roles config spelling mistake.
if (C('Garden.SSO.SynchRoles')) {
    SaveToConfig(
        array('Garden.SSO.SynchRoles' => '', 'Garden.SSO.SyncRoles' => C('Garden.SSO.SynchRoles')),
        '',
        array('RemoveEmpty' => true)
    );
}

$Construct->Table('Session')
    ->Column('SessionID', 'char(32)', false, 'primary')
    ->Column('UserID', 'int', 0)
    ->Column('DateInserted', 'datetime', false)
    ->Column('DateUpdated', 'datetime', false)
    ->Column('TransientKey', 'varchar(12)', false)
    ->Column('Attributes', 'text', null)
    ->Set($Explicit, $Drop);

$Construct->Table('AnalyticsLocal')
    ->Engine('InnoDB')
    ->Column('TimeSlot', 'varchar(8)', false, 'unique')
    ->Column('Views', 'int', null)
    ->Column('EmbedViews', 'int', true)
    ->Set(false, false);

// Only Create the permission table if we are using Garden's permission model.
$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Database = $Database;
$PermissionModel->SQL = $SQL;
$PermissionTableExists = false;
if ($PermissionModel instanceof PermissionModel) {
    $PermissionTableExists = $Construct->TableExists('Permission');

    // Permission Table
    $Construct->Table('Permission')
        ->PrimaryKey('PermissionID')
        ->Column('RoleID', 'int', 0, 'key')
        ->Column('JunctionTable', 'varchar(100)', true)
        ->Column('JunctionColumn', 'varchar(100)', true)
        ->Column('JunctionID', 'int', true)
        // The actual permissions will be added by PermissionModel::Define()
        ->Set($Explicit, $Drop);
}

// Define the set of permissions that Garden uses.
$PermissionModel->Define(array(
    'Garden.Email.View' => 'Garden.SignIn.Allow',
    'Garden.Settings.Manage',
    'Garden.Settings.View',
    'Garden.SignIn.Allow' => 1,
    'Garden.Users.Add',
    'Garden.Users.Edit',
    'Garden.Users.Delete',
    'Garden.Users.Approve',
    'Garden.Activity.Delete',
    'Garden.Activity.View' => 1,
    'Garden.Profiles.View' => 1,
    'Garden.Profiles.Edit' => 'Garden.SignIn.Allow',
    'Garden.Curation.Manage' => 'Garden.Moderation.Manage',
    'Garden.Moderation.Manage',
    'Garden.PersonalInfo.View' => 'Garden.Moderation.Manage',
    'Garden.AdvancedNotifications.Allow',
    'Garden.Community.Manage' => 'Garden.Settings.Manage'
));

$PermissionModel->Undefine(array(
    'Garden.Applications.Manage',
    'Garden.Email.Manage',
    'Garden.Plugins.Manage',
    'Garden.Registration.Manage',
    'Garden.Routes.Manage',
    'Garden.Themes.Manage',
    'Garden.Messages.Manage'
));

//// Photo Table
//$Construct->Table('Photo');
//
//$PhotoTableExists = $Construct->TableExists('Photo');
//
//$Construct
//	->PrimaryKey('PhotoID')
//   ->Column('Name', 'varchar(255)')
//   ->Column('InsertUserID', 'int', TRUE, 'key')
//   ->Column('DateInserted', 'datetime')
//   ->Set($Explicit, $Drop);

// Invitation Table
$Construct->Table('Invitation')
    ->PrimaryKey('InvitationID')
    ->Column('Email', 'varchar(100)', false, 'index')
    ->Column('Name', 'varchar(50)', true)
    ->Column('RoleIDs', 'text', true)
    ->Column('Code', 'varchar(50)', false, 'unique.code')
    ->Column('InsertUserID', 'int', true, 'index.userdate')
    ->Column('DateInserted', 'datetime', false, 'index.userdate')
    ->Column('AcceptedUserID', 'int', true)
    ->Column('DateExpires', 'datetime', true)
    ->Set($Explicit, $Drop);

// Fix negative invitation expiry dates..
$InviteExpiry = C('Garden.Registration.InviteExpiration');
if ($InviteExpiry && substr($InviteExpiry, 0, 1) === '-') {
    $InviteExpiry = substr($InviteExpiry, 1);
    SaveToConfig('Garden.Registration.InviteExpiration', $InviteExpiry);
}

// ActivityType Table
$Construct->Table('ActivityType')
    ->PrimaryKey('ActivityTypeID')
    ->Column('Name', 'varchar(20)')
    ->Column('AllowComments', 'tinyint(1)', '0')
    ->Column('ShowIcon', 'tinyint(1)', '0')
    ->Column('ProfileHeadline', 'varchar(255)', true)
    ->Column('FullHeadline', 'varchar(255)', true)
    ->Column('RouteCode', 'varchar(255)', true)
    ->Column('Notify', 'tinyint(1)', '0')// Add to RegardingUserID's notification list?
    ->Column('Public', 'tinyint(1)', '1')// Should everyone be able to see this, or just the RegardingUserID?
    ->Set($Explicit, $Drop);

// Activity Table
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE)

$Construct->Table('Activity');
$ActivityExists = $Construct->TableExists();
$NotifiedExists = $Construct->ColumnExists('Notified');
$EmailedExists = $Construct->ColumnExists('Emailed');
$CommentActivityIDExists = $Construct->ColumnExists('CommentActivityID');
$NotifyUserIDExists = $Construct->ColumnExists('NotifyUserID');
$DateUpdatedExists = $Construct->ColumnExists('DateUpdated');

if ($ActivityExists) {
    $ActivityIndexes = $Construct->IndexSqlDb();
} else {
    $ActivityIndexes = array();
}

$Construct
    ->PrimaryKey('ActivityID')
    ->Column('ActivityTypeID', 'int')
    ->Column('NotifyUserID', 'int', 0, array('index.Notify', 'index.Recent', 'index.Feed'))// user being notified or -1: public, -2 mods, -3 admins
    ->Column('ActivityUserID', 'int', true, 'index.Feed')
    ->Column('RegardingUserID', 'int', true)// deprecated?
    ->Column('Photo', 'varchar(255)', true)
    ->Column('HeadlineFormat', 'varchar(255)', true)
    ->Column('Story', 'text', true)
    ->Column('Format', 'varchar(10)', true)
    ->Column('Route', 'varchar(255)', true)
    ->Column('RecordType', 'varchar(20)', true)
    ->Column('RecordID', 'int', true)
//   ->Column('CountComments', 'int', '0')
    ->Column('InsertUserID', 'int', true, 'key')
    ->Column('DateInserted', 'datetime')
    ->Column('InsertIPAddress', 'varchar(15)', true)
    ->Column('DateUpdated', 'datetime', !$DateUpdatedExists, array('index', 'index.Recent', 'index.Feed'))
    ->Column('Notified', 'tinyint(1)', 0, 'index.Notify')
    ->Column('Emailed', 'tinyint(1)', 0)
    ->Column('Data', 'text', true)
    ->Set($Explicit, $Drop);

if (isset($ActivityIndexes['IX_Activity_NotifyUserID'])) {
    $Construct->Query("drop index IX_Activity_NotifyUserID on {$Px}Activity");
}

if (isset($ActivityIndexes['FK_Activity_ActivityUserID'])) {
    $Construct->Query("drop index FK_Activity_ActivityUserID on {$Px}Activity");
}

if (isset($ActivityIndexes['FK_Activity_RegardingUserID'])) {
    $Construct->Query("drop index FK_Activity_RegardingUserID on {$Px}Activity");
}

if (!$EmailedExists) {
    $SQL->Put('Activity', array('Emailed' => 1));
}
if (!$NotifiedExists) {
    $SQL->Put('Activity', array('Notified' => 1));
}

if (!$DateUpdatedExists) {
    $SQL->Update('Activity')
        ->Set('DateUpdated', 'DateInserted', false, false)
        ->Put();
}

if (!$NotifyUserIDExists && $ActivityExists) {
    // Update all of the activities that are notifications.
    $SQL->Update('Activity a')
        ->Join('ActivityType at', 'a.ActivityTypeID = at.ActivityTypeID')
        ->Set('a.NotifyUserID', 'a.RegardingUserID', false)
        ->Where('at.Notify', 1)
        ->Put();

    // Update all public activities.
    $SQL->Update('Activity a')
        ->Join('ActivityType at', 'a.ActivityTypeID = at.ActivityTypeID')
        ->Set('a.NotifyUserID', ActivityModel::NOTIFY_PUBLIC)
        ->Where('at.Public', 1)
        ->Where('a.NotifyUserID', 0)
        ->Put();

    $SQL->Delete('Activity', array('NotifyUserID' => 0));
}

$ActivityCommentExists = $Construct->TableExists('ActivityComment');

$Construct
    ->Table('ActivityComment')
    ->PrimaryKey('ActivityCommentID')
    ->Column('ActivityID', 'int', false, 'key')
    ->Column('Body', 'text')
    ->Column('Format', 'varchar(20)')
    ->Column('InsertUserID', 'int')
    ->Column('DateInserted', 'datetime')
    ->Column('InsertIPAddress', 'varchar(15)', true)
    ->Set($Explicit, $Drop);

// Move activity comments to the activity comment table.
if (!$ActivityCommentExists && $CommentActivityIDExists) {
    $Q = "insert {$Px}ActivityComment (ActivityID, Body, Format, InsertUserID, DateInserted, InsertIPAddress)
      select CommentActivityID, Story, 'Text', InsertUserID, DateInserted, InsertIPAddress
      from {$Px}Activity
      where CommentActivityID > 0";
    $Construct->Query($Q);
    $SQL->Delete('Activity', array('CommentActivityID >' => 0));
}

// Insert some activity types
///  %1 = ActivityName
///  %2 = ActivityName Possessive: Username
///  %3 = RegardingName
///  %4 = RegardingName Possessive: Username, his, her, your
///  %5 = Link to RegardingName's Wall
///  %6 = his/her
///  %7 = he/she
///  %8 = RouteCode & Route
if ($SQL->GetWhere('ActivityType', array('Name' => 'SignIn'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'SignIn', 'FullHeadline' => '%1$s signed in.', 'ProfileHeadline' => '%1$s signed in.'));
}
if ($SQL->GetWhere('ActivityType', array('Name' => 'Join'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'Join', 'FullHeadline' => '%1$s joined.', 'ProfileHeadline' => '%1$s joined.'));
}
if ($SQL->GetWhere('ActivityType', array('Name' => 'JoinInvite'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'JoinInvite', 'FullHeadline' => '%1$s accepted %4$s invitation for membership.', 'ProfileHeadline' => '%1$s accepted %4$s invitation for membership.'));
}
if ($SQL->GetWhere('ActivityType', array('Name' => 'JoinApproved'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'JoinApproved', 'FullHeadline' => '%1$s approved %4$s membership application.', 'ProfileHeadline' => '%1$s approved %4$s membership application.'));
}
$SQL->Replace('ActivityType', array('AllowComments' => '1', 'FullHeadline' => '%1$s created an account for %3$s.', 'ProfileHeadline' => '%1$s created an account for %3$s.'), array('Name' => 'JoinCreated'), true);

if ($SQL->GetWhere('ActivityType', array('Name' => 'AboutUpdate'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'AboutUpdate', 'FullHeadline' => '%1$s updated %6$s profile.', 'ProfileHeadline' => '%1$s updated %6$s profile.'));
}
if ($SQL->GetWhere('ActivityType', array('Name' => 'WallComment'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '1', 'ShowIcon' => '1', 'Name' => 'WallComment', 'FullHeadline' => '%1$s wrote on %4$s %5$s.', 'ProfileHeadline' => '%1$s wrote:'));
}
if ($SQL->GetWhere('ActivityType', array('Name' => 'PictureChange'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '1', 'Name' => 'PictureChange', 'FullHeadline' => '%1$s changed %6$s profile picture.', 'ProfileHeadline' => '%1$s changed %6$s profile picture.'));
}
//if ($SQL->GetWhere('ActivityType', array('Name' => 'RoleChange'))->NumRows() == 0)
$SQL->Replace('ActivityType', array('AllowComments' => '1', 'FullHeadline' => '%1$s changed %4$s permissions.', 'ProfileHeadline' => '%1$s changed %4$s permissions.', 'Notify' => '1'), array('Name' => 'RoleChange'), true);
if ($SQL->GetWhere('ActivityType', array('Name' => 'ActivityComment'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'ShowIcon' => '1', 'Name' => 'ActivityComment', 'FullHeadline' => '%1$s commented on %4$s %8$s.', 'ProfileHeadline' => '%1$s', 'RouteCode' => 'activity', 'Notify' => '1'));
}
if ($SQL->GetWhere('ActivityType', array('Name' => 'Import'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'Import', 'FullHeadline' => '%1$s imported data.', 'ProfileHeadline' => '%1$s imported data.', 'Notify' => '1', 'Public' => '0'));
}
//if ($SQL->GetWhere('ActivityType', array('Name' => 'Banned'))->NumRows() == 0)
$SQL->Replace('ActivityType', array('AllowComments' => '0', 'FullHeadline' => '%1$s banned %3$s.', 'ProfileHeadline' => '%1$s banned %3$s.', 'Notify' => '0', 'Public' => '1'), array('Name' => 'Banned'), true);
//if ($SQL->GetWhere('ActivityType', array('Name' => 'Unbanned'))->NumRows() == 0)
$SQL->Replace('ActivityType', array('AllowComments' => '0', 'FullHeadline' => '%1$s un-banned %3$s.', 'ProfileHeadline' => '%1$s un-banned %3$s.', 'Notify' => '0', 'Public' => '1'), array('Name' => 'Unbanned'), true);

// Applicant activity
if ($SQL->GetWhere('ActivityType', array('Name' => 'Applicant'))->NumRows() == 0) {
    $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'Applicant', 'FullHeadline' => '%1$s applied for membership.', 'ProfileHeadline' => '%1$s applied for membership.', 'Notify' => '1', 'Public' => '0'));
}

$WallPostType = $SQL->GetWhere('ActivityType', array('Name' => 'WallPost'))->FirstRow(DATASET_TYPE_ARRAY);
if (!$WallPostType) {
    $WallPostTypeID = $SQL->Insert('ActivityType', array('AllowComments' => '1', 'ShowIcon' => '1', 'Name' => 'WallPost', 'FullHeadline' => '%3$s wrote on %2$s %5$s.', 'ProfileHeadline' => '%3$s wrote:'));
    $WallCommentTypeID = $SQL->GetWhere('ActivityType', array('Name' => 'WallComment'))->Value('ActivityTypeID');

    // Update all old wall comments to wall posts.
    $SQL->Update('Activity')
        ->Set('ActivityTypeID', $WallPostTypeID)
        ->Set('ActivityUserID', 'RegardingUserID', false)
        ->Set('RegardingUserID', 'InsertUserID', false)
        ->Where('ActivityTypeID', $WallCommentTypeID)
        ->Where('RegardingUserID is not null')
        ->Put();
}

$ActivityModel = new ActivityModel();
$ActivityModel->DefineType('Default');
$ActivityModel->DefineType('Registration');
$ActivityModel->DefineType('Status');
$ActivityModel->DefineType('Ban');

// Message Table
$Construct->Table('Message')
    ->PrimaryKey('MessageID')
    ->Column('Content', 'text')
    ->Column('Format', 'varchar(20)', true)
    ->Column('AllowDismiss', 'tinyint(1)', '1')
    ->Column('Enabled', 'tinyint(1)', '1')
    ->Column('Application', 'varchar(255)', true)
    ->Column('Controller', 'varchar(255)', true)
    ->Column('Method', 'varchar(255)', true)
    ->Column('CategoryID', 'int', true)
    ->Column('IncludeSubcategories', 'tinyint', '0')
    ->Column('AssetTarget', 'varchar(20)', true)
    ->Column('CssClass', 'varchar(20)', true)
    ->Column('Sort', 'int', true)
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

$Construct->Table('Tag');
$FullNameColumnExists = $Construct->ColumnExists('FullName');
$TagCategoryColumnExists = $Construct->ColumnExists('CategoryID');

// This is a fix for erroneous unique constraint.
if ($Construct->TableExists('Tag') && $TagCategoryColumnExists) {
    $Db = Gdn::Database();
    $Px = Gdn::Database()->DatabasePrefix;

    $DupTags = Gdn::SQL()
        ->Select('Name, CategoryID')
        ->Select('TagID', 'min', 'TagID')
        ->Select('TagID', 'count', 'CountTags')
        ->From('Tag')
        ->GroupBy('Name')
        ->GroupBy('CategoryID')
        ->Having('CountTags >', 1)
        ->Get()->ResultArray();

    foreach ($DupTags as $Row) {
        $Name = $Row['Name'];
        $CategoryID = $Row['CategoryID'];
        $TagID = $Row['TagID'];
        // Get the tags that need to be deleted.
        $DeleteTags = Gdn::SQL()->GetWhere('Tag', array('Name' => $Name, 'CategoryID' => $CategoryID, 'TagID <> ' => $TagID))->ResultArray();
        foreach ($DeleteTags as $DRow) {
            // Update all of the discussions to the new tag.
            Gdn::SQL()->Options('Ignore', true)->Put(
                'TagDiscussion',
                array('TagID' => $TagID),
                array('TagID' => $DRow['TagID'])
            );

            // Delete the tag.
            Gdn::SQL()->Delete('Tag', array('TagID' => $DRow['TagID']));
        }
    }
}

$Construct->Table('Tag')
    ->PrimaryKey('TagID')
    ->Column('Name', 'varchar(100)', false, 'unique')
    ->Column('FullName', 'varchar(100)', !$FullNameColumnExists, 'index')
    ->Column('Type', 'varchar(20)', '', 'index')
    ->Column('ParentTagID', 'int', true, 'key')
    ->Column('InsertUserID', 'int', true, 'key')
    ->Column('DateInserted', 'datetime')
    ->Column('CategoryID', 'int', -1, 'unique')
    ->Engine('InnoDB')
    ->Set($Explicit, $Drop);

if (!$FullNameColumnExists) {
    Gdn::SQL()->Update('Tag')
        ->Set('FullName', 'Name', false, false)
        ->Put();

    $Construct->Table('Tag')
        ->Column('FullName', 'varchar(255)', false, 'index')
        ->Set();
}

$Construct->Table('Log')
    ->PrimaryKey('LogID')
    ->Column('Operation', array('Delete', 'Edit', 'Spam', 'Moderate', 'Pending', 'Ban', 'Error'), false, 'index')
    ->Column('RecordType', array('Discussion', 'Comment', 'User', 'Registration', 'Activity', 'ActivityComment', 'Configuration', 'Group'), false, 'index')
    ->Column('TransactionLogID', 'int', null)
    ->Column('RecordID', 'int', null, 'index')
    ->Column('RecordUserID', 'int', null, 'index')// user responsible for the record; indexed for user deletion
    ->Column('RecordDate', 'datetime')
    ->Column('RecordIPAddress', 'varchar(15)', null, 'index')
    ->Column('InsertUserID', 'int')// user that put record in the log
    ->Column('DateInserted', 'datetime', false, 'index')// date item added to log
    ->Column('InsertIPAddress', 'varchar(15)', null)
    ->Column('OtherUserIDs', 'varchar(255)', null)
    ->Column('DateUpdated', 'datetime', null)
    ->Column('ParentRecordID', 'int', null, 'index')
    ->Column('CategoryID', 'int', null, 'key')
    ->Column('Data', 'mediumtext', null)// the data from the record.
    ->Column('CountGroup', 'int', null)
    ->Engine('InnoDB')
    ->Set($Explicit, $Drop);

$Construct->Table('Regarding')
    ->PrimaryKey('RegardingID')
    ->Column('Type', 'varchar(100)', false, 'key')
    ->Column('InsertUserID', 'int', false)
    ->Column('DateInserted', 'datetime', false)
    ->Column('ForeignType', 'varchar(32)', false)
    ->Column('ForeignID', 'int(11)', false)
    ->Column('OriginalContent', 'text', true)
    ->Column('ParentType', 'varchar(32)', true)
    ->Column('ParentID', 'int(11)', true)
    ->Column('ForeignURL', 'varchar(255)', true)
    ->Column('Comment', 'text', false)
    ->Column('Reports', 'int(11)', true)
    ->Engine('InnoDB')
    ->Set($Explicit, $Drop);

$Construct->Table('Ban')
    ->PrimaryKey('BanID')
    ->Column('BanType', array('IPAddress', 'Name', 'Email'), false, 'unique')
    ->Column('BanValue', 'varchar(50)', false, 'unique')
    ->Column('Notes', 'varchar(255)', null)
    ->Column('CountUsers', 'uint', 0)
    ->Column('CountBlockedRegistrations', 'uint', 0)
    ->Column('InsertUserID', 'int')
    ->Column('DateInserted', 'datetime')
    ->Column('InsertIPAddress', 'varchar(15)', true)
    ->Column('UpdateUserID', 'int', true)
    ->Column('DateUpdated', 'datetime', true)
    ->Column('UpdateIPAddress', 'varchar(15)', true)
    ->Engine('InnoDB')
    ->Set($Explicit, $Drop);

$Construct->Table('Spammer')
    ->Column('UserID', 'int', false, 'primary')
    ->Column('CountSpam', 'usmallint', 0)
    ->Column('CountDeletedSpam', 'usmallint', 0)
    ->Set($Explicit, $Drop);

$Construct
    ->Table('Media')
    ->PrimaryKey('MediaID')
    ->Column('Name', 'varchar(255)')
    ->Column('Path', 'varchar(255)')
    ->Column('Type', 'varchar(128)')
    ->Column('Size', 'int(11)')
    ->Column('InsertUserID', 'int(11)')
    ->Column('DateInserted', 'datetime')
    ->Column('ForeignID', 'int(11)', true, 'index.Foreign')
    ->Column('ForeignTable', 'varchar(24)', true, 'index.Foreign')
    ->Column('ImageWidth', 'usmallint', null)
    ->Column('ImageHeight', 'usmallint', null)
//   ->Column('StorageMethod', 'varchar(24)')
    ->Column('ThumbWidth', 'usmallint', null)
    ->Column('ThumbHeight', 'usmallint', null)
    ->Column('ThumbPath', 'varchar(255)', null)
    ->Set(false, false);

// Merge backup.
$Construct
    ->Table('UserMerge')
    ->PrimaryKey('MergeID')
    ->Column('OldUserID', 'int', false, 'key')
    ->Column('NewUserID', 'int', false, 'key')
    ->Column('DateInserted', 'datetime')
    ->Column('InsertUserID', 'int')
    ->Column('DateUpdated', 'datetime', true)
    ->Column('UpdateUserID', 'int', true)
    ->Column('Attributes', 'text', true)
    ->Set();

$Construct
    ->Table('UserMergeItem')
    ->Column('MergeID', 'int', false, 'key')
    ->Column('Table', 'varchar(30)')
    ->Column('Column', 'varchar(30)')
    ->Column('RecordID', 'int')
    ->Column('OldUserID', 'int')
    ->Column('NewUserID', 'int')
    ->Set();

$Construct
    ->Table('Attachment')
    ->PrimaryKey('AttachmentID')
    ->Column('Type', 'varchar(64)')// ex: zendesk-case, vendor-item
    ->Column('ForeignID', 'varchar(50)', false, 'index')// ex: d-123 for DiscussionID 123, u-555 for UserID 555
    ->Column('ForeignUserID', 'int', false, 'key')// the user id of the record we are attached to (de-normalization)
    ->Column('Source', 'varchar(64)')// ex: Zendesk, Vendor
    ->Column('SourceID', 'varchar(32)')// ex: 1
    ->Column('SourceURL', 'varchar(255)')
    ->Column('Attributes', 'text', true)
    ->Column('DateInserted', 'datetime')
    ->Column('InsertUserID', 'int', false, 'key')
    ->Column('InsertIPAddress', 'varchar(64)')
    ->Column('DateUpdated', 'datetime', true)
    ->Column('UpdateUserID', 'int', true)
    ->Column('UpdateIPAddress', 'varchar(15)', true)
    ->Set($Explicit, $Drop);

// Save the current input formatter to the user's config.
// This will allow us to change the default later and grandfather existing forums in.
SaveToConfig('Garden.InputFormatter', C('Garden.InputFormatter'));

// Make sure the default locale is in its canonical form.
$currentLocale = C('Garden.Locale');
$canonicalLocale = Gdn_Locale::Canonicalize($currentLocale);
if ($currentLocale !== $canonicalLocale) {
    SaveToConfig('Garden.Locale', $canonicalLocale);
}

// We need to undo cleditor's bad behavior for our reformed users.
// If you still need to manipulate this, do it in memory instead (SAVE = false).
if (!C('Garden.Html.SafeStyles')) {
    RemoveFromConfig('Garden.Html.SafeStyles');
}

// Make sure the smarty folders exist.
if (!file_exists(PATH_CACHE.'/Smarty')) {
    @mkdir(PATH_CACHE.'/Smarty');
}
if (!file_exists(PATH_CACHE.'/Smarty/cache')) {
    @mkdir(PATH_CACHE.'/Smarty/cache');
}
if (!file_exists(PATH_CACHE.'/Smarty/compile')) {
    @mkdir(PATH_CACHE.'/Smarty/compile');
}
