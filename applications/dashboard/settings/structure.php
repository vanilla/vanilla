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

$Database = Gdn::database();
$SQL = $Database->sql();
$Construct = $Database->structure();
$Px = $Database->DatabasePrefix;

// Role Table
$Construct->table('Role');

$RoleTableExists = $Construct->tableExists();
$RoleTypeExists = $Construct->columnExists('Type');

$Construct
    ->primaryKey('RoleID')
    ->column('Name', 'varchar(100)')
    ->column('Description', 'varchar(500)', true)
    ->column('Type', array(RoleModel::TYPE_GUEST, RoleModel::TYPE_UNCONFIRMED, RoleModel::TYPE_APPLICANT, RoleModel::TYPE_MEMBER, RoleModel::TYPE_MODERATOR, RoleModel::TYPE_ADMINISTRATOR), true)
    ->column('Sort', 'int', true)
    ->column('Deletable', 'tinyint(1)', '1')
    ->column('CanSession', 'tinyint(1)', '1')
    ->column('PersonalInfo', 'tinyint(1)', '0')
    ->set($Explicit, $Drop);

$RoleModel = new RoleModel();

if (!$RoleTableExists || $Drop) {
    // Define default roles.
    $RoleModel->Database = $Database;
    $RoleModel->SQL = $SQL;
    $Sort = 1;
    $RoleModel->define(array('Name' => 'Guest', 'Type' => RoleModel::TYPE_GUEST, 'RoleID' => 2, 'Sort' => $Sort++, 'Deletable' => '0', 'CanSession' => '0', 'Description' => t('Guest Role Description', 'Guests can only view content. Anyone browsing the site who is not signed in is considered to be a "Guest".')));
    $RoleModel->define(array('Name' => 'Unconfirmed', 'Type' => RoleModel::TYPE_UNCONFIRMED, 'RoleID' => 3, 'Sort' => $Sort++, 'Deletable' => '0', 'CanSession' => '1', 'Description' => t('Unconfirmed Role Description', 'Users must confirm their emails before becoming full members. They get assigned to this role.')));
    $RoleModel->define(array('Name' => 'Applicant', 'Type' => RoleModel::TYPE_APPLICANT, 'RoleID' => 4, 'Sort' => $Sort++, 'Deletable' => '0', 'CanSession' => '1', 'Description' => t('Applicant Role Description', 'Users who have applied for membership, but have not yet been accepted. They have the same permissions as guests.')));
    $RoleModel->define(array('Name' => 'Member', 'Type' => RoleModel::TYPE_MEMBER, 'RoleID' => 8, 'Sort' => $Sort++, 'Deletable' => '1', 'CanSession' => '1', 'Description' => t('Member Role Description', 'Members can participate in discussions.')));
    $RoleModel->define(array('Name' => 'Moderator', 'Type' => RoleModel::TYPE_MODERATOR, 'RoleID' => 32, 'Sort' => $Sort++, 'Deletable' => '1', 'CanSession' => '1', 'Description' => t('Moderator Role Description', 'Moderators have permission to edit most content.')));
    $RoleModel->define(array('Name' => 'Administrator', 'Type' => RoleModel::TYPE_ADMINISTRATOR, 'RoleID' => 16, 'Sort' => $Sort++, 'Deletable' => '1', 'CanSession' => '1', 'Description' => t('Administrator Role Description', 'Administrators have permission to do anything.')));
}

// User Table
$Construct->table('User');

$PhotoIDExists = $Construct->columnExists('PhotoID');
$PhotoExists = $Construct->columnExists('Photo');
$UserExists = $Construct->tableExists();
$ConfirmedExists = $Construct->columnExists('Confirmed');

$Construct
    ->primaryKey('UserID')
    ->column('Name', 'varchar(50)', false, 'key')
    ->column('Password', 'varbinary(100)')// keep this longer because of some imports.
    ->column('HashMethod', 'varchar(10)', true)
    ->column('Photo', 'varchar(255)', null)
    ->column('Title', 'varchar(100)', null)
    ->column('Location', 'varchar(100)', null)
    ->column('About', 'text', true)
    ->column('Email', 'varchar(100)', false, 'index')
    ->column('ShowEmail', 'tinyint(1)', '0')
    ->column('Gender', array('u', 'm', 'f'), 'u')
    ->column('CountVisits', 'int', '0')
    ->column('CountInvitations', 'int', '0')
    ->column('CountNotifications', 'int', null)
    ->column('InviteUserID', 'int', true)
    ->column('DiscoveryText', 'text', true)
    ->column('Preferences', 'text', true)
    ->column('Permissions', 'text', true)
    ->column('Attributes', 'text', true)
    ->column('DateSetInvitations', 'datetime', true)
    ->column('DateOfBirth', 'datetime', true)
    ->column('DateFirstVisit', 'datetime', true)
    ->column('DateLastActive', 'datetime', true, 'index')
    ->column('LastIPAddress', 'varchar(15)', true)
    ->column('AllIPAddresses', 'varchar(100)', true)
    ->column('DateInserted', 'datetime', false, 'index')
    ->column('InsertIPAddress', 'varchar(15)', true)
    ->column('DateUpdated', 'datetime', true)
    ->column('UpdateIPAddress', 'varchar(15)', true)
    ->column('HourOffset', 'int', '0')
    ->column('Score', 'float', null)
    ->column('Admin', 'tinyint(1)', '0')
    ->column('Confirmed', 'tinyint(1)', '1')// 1 means email confirmed, otherwise not confirmed
    ->column('Verified', 'tinyint(1)', '0')// 1 means verified (non spammer), otherwise not verified
    ->column('Banned', 'tinyint(1)', '0')// 1 means banned, otherwise not banned
    ->column('Deleted', 'tinyint(1)', '0')
    ->column('Points', 'int', 0)
    ->set($Explicit, $Drop);

// Modify all users with ConfirmEmail role to be unconfirmed
if ($UserExists && !$ConfirmedExists) {
    $ConfirmEmail = c('Garden.Registration.ConfirmEmail', false);
    $ConfirmEmailRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);
    if ($ConfirmEmail && !empty($ConfirmEmailRoleID)) {
        // Select unconfirmed users
        $Users = Gdn::sql()->select('UserID')->from('UserRole')->where('RoleID', $ConfirmEmailRoleID)->get();
        $UserIDs = array();
        while ($User = $Users->nextRow(DATASET_TYPE_ARRAY)) {
            $UserIDs[] = $User['UserID'];
        }

        // Update
        Gdn::sql()->update('User')->set('Confirmed', 0)->whereIn('UserID', $UserIDs)->put();
        Gdn::sql()->delete('UserRole', array('RoleID' => $ConfirmEmailRoleID, 'UserID' => $UserIDs));
    }
}

// Make sure the system user is okay.
$SystemUserID = c('Garden.SystemUserID');
if ($SystemUserID) {
    $SysUser = Gdn::userModel()->getID($SystemUserID);

    if (!$SysUser || val('Deleted', $SysUser) || val('Admin', $SysUser) != 2) {
        $SystemUserID = false;
        removeFromConfig('Garden.SystemUserID');
    }
}

if (!$SystemUserID) {
    // Try and find a system user.
    $SystemUserID = Gdn::sql()->getWhere('User', array('Name' => 'System', 'Admin' => 2))->value('UserID');
    if ($SystemUserID) {
        saveToConfig('Garden.SystemUserID', $SystemUserID);
    } else {
        // Create a new one if we couldn't find one.
        Gdn::userModel()->getSystemUserID();
    }
}

// UserRole Table
$Construct->table('UserRole');

$UserRoleExists = $Construct->tableExists();

$Construct
    ->column('UserID', 'int', false, 'primary')
    ->column('RoleID', 'int', false, array('primary', 'index'))
    ->set($Explicit, $Drop);

// Fix old default roles that were stored in the config and user-role table.
if ($RoleTableExists && $UserRoleExists && $RoleTypeExists) {
    $types = $RoleModel->getAllDefaultRoles();
    if ($v = c('Garden.Registration.ApplicantRoleID')) {
        $SQL->update('Role')
            ->set('Type', RoleModel::TYPE_APPLICANT)
            ->where('RoleID', $types[RoleModel::TYPE_APPLICANT])
            ->put();
//      RemoveFromConfig('Garden.Registration.ApplicantRoleID');
    }

    if ($v = c('Garden.Registration.DefaultRoles')) {
        $SQL->update('Role')
            ->set('Type', RoleModel::TYPE_MEMBER)
            ->where('RoleID', $types[RoleModel::TYPE_MEMBER])
            ->put();
//      RemoveFromConfig('Garden.Registration.DefaultRoles');
    }

    if ($v = c('Garden.Registration.ConfirmEmailRole')) {
        $SQL->update('Role')
            ->set('Type', RoleModel::TYPE_UNCONFIRMED)
            ->where('RoleID', $types[RoleModel::TYPE_UNCONFIRMED])
            ->put();
//      RemoveFromConfig('Garden.Registration.ConfirmEmailRole');
    }

    $guestRoleIDs = Gdn::sql()->getWhere('UserRole', array('UserID' => 0))->resultArray();
    if (!empty($guestRoleIDs)) {
        $SQL->update('Role')
            ->set('Type', RoleModel::TYPE_GUEST)
            ->where('RoleID', $types[RoleModel::TYPE_GUEST])
            ->put();

        $SQL->delete('UserRole', array('UserID' => 0));
    }
}

if (!$UserRoleExists) {
    // Assign the admin user to admin role.
    $adminRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_ADMINISTRATOR);

    foreach ($adminRoleIDs as $id) {
        $SQL->replace('UserRole', array(), array('UserID' => 1, 'RoleID' => $id));
    }
}

// User Meta Table
$Construct->table('UserMeta')
    ->column('UserID', 'int', false, 'primary')
    ->column('Name', 'varchar(100)', false, array('primary', 'index'))
    ->column('Value', 'text', true)
    ->set($Explicit, $Drop);

// User Points Table
$Construct->table('UserPoints')
    ->column('SlotType', array('d', 'w', 'm', 'y', 'a'), false, 'primary')
    ->column('TimeSlot', 'datetime', false, 'primary')
    ->column('Source', 'varchar(10)', 'Total', 'primary')
    ->column('CategoryID', 'int', 0, 'primary')
    ->column('UserID', 'int', false, 'primary')
    ->column('Points', 'int', 0)
    ->set($Explicit, $Drop);

// Create the authentication table.
$Construct->table('UserAuthentication')
    ->column('ForeignUserKey', 'varchar(100)', false, 'primary')
    ->column('ProviderKey', 'varchar(64)', false, 'primary')
    ->column('UserID', 'int', false, 'key')
    ->set($Explicit, $Drop);

$Construct->table('UserAuthenticationProvider')
    ->column('AuthenticationKey', 'varchar(64)', false, 'primary')
    ->column('AuthenticationSchemeAlias', 'varchar(32)', false)
    ->column('Name', 'varchar(50)', true)
    ->column('URL', 'varchar(255)', true)
    ->column('AssociationSecret', 'text', true)
    ->column('AssociationHashMethod', 'varchar(20)', true)
    ->column('AuthenticateUrl', 'varchar(255)', true)
    ->column('RegisterUrl', 'varchar(255)', true)
    ->column('SignInUrl', 'varchar(255)', true)
    ->column('SignOutUrl', 'varchar(255)', true)
    ->column('PasswordUrl', 'varchar(255)', true)
    ->column('ProfileUrl', 'varchar(255)', true)
    ->column('Attributes', 'text', true)
    ->column('Active', 'tinyint', '1')
    ->column('IsDefault', 'tinyint', 0)
    ->set($Explicit, $Drop);

$Construct->table('UserAuthenticationNonce')
    ->column('Nonce', 'varchar(100)', false, 'primary')
    ->column('Token', 'varchar(128)', false)
    ->column('Timestamp', 'timestamp', false)
    ->set($Explicit, $Drop);

$Construct->table('UserAuthenticationToken')
    ->column('Token', 'varchar(128)', false, 'primary')
    ->column('ProviderKey', 'varchar(64)', false, 'primary')
    ->column('ForeignUserKey', 'varchar(100)', true)
    ->column('TokenSecret', 'varchar(64)', false)
    ->column('TokenType', array('request', 'access'), false)
    ->column('Authorized', 'tinyint(1)', false)
    ->column('Timestamp', 'timestamp', false)
    ->column('Lifetime', 'int', false)
    ->set($Explicit, $Drop);

// Fix the sync roles config spelling mistake.
if (c('Garden.SSO.SynchRoles')) {
    saveToConfig(
        array('Garden.SSO.SynchRoles' => '', 'Garden.SSO.SyncRoles' => c('Garden.SSO.SynchRoles')),
        '',
        array('RemoveEmpty' => true)
    );
}

$Construct->table('Session')
    ->column('SessionID', 'char(32)', false, 'primary')
    ->column('UserID', 'int', 0)
    ->column('DateInserted', 'datetime', false)
    ->column('DateUpdated', 'datetime', false)
    ->column('TransientKey', 'varchar(12)', false)
    ->column('Attributes', 'text', null)
    ->set($Explicit, $Drop);

$Construct->table('AnalyticsLocal')
    ->engine('InnoDB')
    ->column('TimeSlot', 'varchar(8)', false, 'unique')
    ->column('Views', 'int', null)
    ->column('EmbedViews', 'int', true)
    ->set(false, false);

// Only Create the permission table if we are using Garden's permission model.
$PermissionModel = Gdn::permissionModel();
$PermissionModel->Database = $Database;
$PermissionModel->SQL = $SQL;
$PermissionTableExists = false;
if ($PermissionModel instanceof PermissionModel) {
    $PermissionTableExists = $Construct->tableExists('Permission');

    // Permission Table
    $Construct->table('Permission')
        ->primaryKey('PermissionID')
        ->column('RoleID', 'int', 0, 'key')
        ->column('JunctionTable', 'varchar(100)', true)
        ->column('JunctionColumn', 'varchar(100)', true)
        ->column('JunctionID', 'int', true)
        // The actual permissions will be added by PermissionModel::Define()
        ->set($Explicit, $Drop);
}

// Define the set of permissions that Garden uses.
$PermissionModel->define(array(
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

$PermissionModel->undefine(array(
    'Garden.Applications.Manage',
    'Garden.Email.Manage',
    'Garden.Plugins.Manage',
    'Garden.Registration.Manage',
    'Garden.Routes.Manage',
    'Garden.Themes.Manage',
    'Garden.Messages.Manage'
));

//// Photo Table
//$Construct->table('Photo');
//
//$PhotoTableExists = $Construct->TableExists('Photo');
//
//$Construct
//	->PrimaryKey('PhotoID')
//   ->column('Name', 'varchar(255)')
//   ->column('InsertUserID', 'int', TRUE, 'key')
//   ->column('DateInserted', 'datetime')
//   ->set($Explicit, $Drop);

// Invitation Table
$Construct->table('Invitation')
    ->primaryKey('InvitationID')
    ->column('Email', 'varchar(100)', false, 'index')
    ->column('Name', 'varchar(50)', true)
    ->column('RoleIDs', 'text', true)
    ->column('Code', 'varchar(50)', false, 'unique.code')
    ->column('InsertUserID', 'int', true, 'index.userdate')
    ->column('DateInserted', 'datetime', false, 'index.userdate')
    ->column('AcceptedUserID', 'int', true)
    ->column('DateExpires', 'datetime', true)
    ->set($Explicit, $Drop);

// Fix negative invitation expiry dates..
$InviteExpiry = c('Garden.Registration.InviteExpiration');
if ($InviteExpiry && substr($InviteExpiry, 0, 1) === '-') {
    $InviteExpiry = substr($InviteExpiry, 1);
    saveToConfig('Garden.Registration.InviteExpiration', $InviteExpiry);
}

// ActivityType Table
$Construct->table('ActivityType')
    ->primaryKey('ActivityTypeID')
    ->column('Name', 'varchar(20)')
    ->column('AllowComments', 'tinyint(1)', '0')
    ->column('ShowIcon', 'tinyint(1)', '0')
    ->column('ProfileHeadline', 'varchar(255)', true)
    ->column('FullHeadline', 'varchar(255)', true)
    ->column('RouteCode', 'varchar(255)', true)
    ->column('Notify', 'tinyint(1)', '0')// Add to RegardingUserID's notification list?
    ->column('Public', 'tinyint(1)', '1')// Should everyone be able to see this, or just the RegardingUserID?
    ->set($Explicit, $Drop);

// Activity Table
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = null, $KeyType = FALSE, $AutoIncrement = FALSE)

$Construct->table('Activity');
$ActivityExists = $Construct->tableExists();
$NotifiedExists = $Construct->columnExists('Notified');
$EmailedExists = $Construct->columnExists('Emailed');
$CommentActivityIDExists = $Construct->columnExists('CommentActivityID');
$NotifyUserIDExists = $Construct->columnExists('NotifyUserID');
$DateUpdatedExists = $Construct->columnExists('DateUpdated');

if ($ActivityExists) {
    $ActivityIndexes = $Construct->indexSqlDb();
} else {
    $ActivityIndexes = array();
}

$Construct
    ->primaryKey('ActivityID')
    ->column('ActivityTypeID', 'int')
    ->column('NotifyUserID', 'int', 0, array('index.Notify', 'index.Recent', 'index.Feed'))// user being notified or -1: public, -2 mods, -3 admins
    ->column('ActivityUserID', 'int', true, 'index.Feed')
    ->column('RegardingUserID', 'int', true)// deprecated?
    ->column('Photo', 'varchar(255)', true)
    ->column('HeadlineFormat', 'varchar(255)', true)
    ->column('Story', 'text', true)
    ->column('Format', 'varchar(10)', true)
    ->column('Route', 'varchar(255)', true)
    ->column('RecordType', 'varchar(20)', true)
    ->column('RecordID', 'int', true)
//   ->column('CountComments', 'int', '0')
    ->column('InsertUserID', 'int', true, 'key')
    ->column('DateInserted', 'datetime')
    ->column('InsertIPAddress', 'varchar(15)', true)
    ->column('DateUpdated', 'datetime', !$DateUpdatedExists, array('index', 'index.Recent', 'index.Feed'))
    ->column('Notified', 'tinyint(1)', 0, 'index.Notify')
    ->column('Emailed', 'tinyint(1)', 0)
    ->column('Data', 'text', true)
    ->set($Explicit, $Drop);

if (isset($ActivityIndexes['IX_Activity_NotifyUserID'])) {
    $Construct->query("drop index IX_Activity_NotifyUserID on {$Px}Activity");
}

if (isset($ActivityIndexes['FK_Activity_ActivityUserID'])) {
    $Construct->query("drop index FK_Activity_ActivityUserID on {$Px}Activity");
}

if (isset($ActivityIndexes['FK_Activity_RegardingUserID'])) {
    $Construct->query("drop index FK_Activity_RegardingUserID on {$Px}Activity");
}

if (!$EmailedExists) {
    $SQL->put('Activity', array('Emailed' => 1));
}
if (!$NotifiedExists) {
    $SQL->put('Activity', array('Notified' => 1));
}

if (!$DateUpdatedExists) {
    $SQL->update('Activity')
        ->set('DateUpdated', 'DateInserted', false, false)
        ->put();
}

if (!$NotifyUserIDExists && $ActivityExists) {
    // Update all of the activities that are notifications.
    $SQL->update('Activity a')
        ->join('ActivityType at', 'a.ActivityTypeID = at.ActivityTypeID')
        ->set('a.NotifyUserID', 'a.RegardingUserID', false)
        ->where('at.Notify', 1)
        ->put();

    // Update all public activities.
    $SQL->update('Activity a')
        ->join('ActivityType at', 'a.ActivityTypeID = at.ActivityTypeID')
        ->set('a.NotifyUserID', ActivityModel::NOTIFY_PUBLIC)
        ->where('at.Public', 1)
        ->where('a.NotifyUserID', 0)
        ->put();

    $SQL->delete('Activity', array('NotifyUserID' => 0));
}

$ActivityCommentExists = $Construct->tableExists('ActivityComment');

$Construct
    ->table('ActivityComment')
    ->primaryKey('ActivityCommentID')
    ->column('ActivityID', 'int', false, 'key')
    ->column('Body', 'text')
    ->column('Format', 'varchar(20)')
    ->column('InsertUserID', 'int')
    ->column('DateInserted', 'datetime')
    ->column('InsertIPAddress', 'varchar(15)', true)
    ->set($Explicit, $Drop);

// Move activity comments to the activity comment table.
if (!$ActivityCommentExists && $CommentActivityIDExists) {
    $Q = "insert {$Px}ActivityComment (ActivityID, Body, Format, InsertUserID, DateInserted, InsertIPAddress)
      select CommentActivityID, Story, 'Text', InsertUserID, DateInserted, InsertIPAddress
      from {$Px}Activity
      where CommentActivityID > 0";
    $Construct->query($Q);
    $SQL->delete('Activity', array('CommentActivityID >' => 0));
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
if ($SQL->getWhere('ActivityType', array('Name' => 'SignIn'))->numRows() == 0) {
    $SQL->insert('ActivityType', array('AllowComments' => '0', 'Name' => 'SignIn', 'FullHeadline' => '%1$s signed in.', 'ProfileHeadline' => '%1$s signed in.'));
}
if ($SQL->getWhere('ActivityType', array('Name' => 'Join'))->numRows() == 0) {
    $SQL->insert('ActivityType', array('AllowComments' => '1', 'Name' => 'Join', 'FullHeadline' => '%1$s joined.', 'ProfileHeadline' => '%1$s joined.'));
}
if ($SQL->getWhere('ActivityType', array('Name' => 'JoinInvite'))->numRows() == 0) {
    $SQL->insert('ActivityType', array('AllowComments' => '1', 'Name' => 'JoinInvite', 'FullHeadline' => '%1$s accepted %4$s invitation for membership.', 'ProfileHeadline' => '%1$s accepted %4$s invitation for membership.'));
}
if ($SQL->getWhere('ActivityType', array('Name' => 'JoinApproved'))->numRows() == 0) {
    $SQL->insert('ActivityType', array('AllowComments' => '1', 'Name' => 'JoinApproved', 'FullHeadline' => '%1$s approved %4$s membership application.', 'ProfileHeadline' => '%1$s approved %4$s membership application.'));
}
$SQL->replace('ActivityType', array('AllowComments' => '1', 'FullHeadline' => '%1$s created an account for %3$s.', 'ProfileHeadline' => '%1$s created an account for %3$s.'), array('Name' => 'JoinCreated'), true);

if ($SQL->getWhere('ActivityType', array('Name' => 'AboutUpdate'))->numRows() == 0) {
    $SQL->insert('ActivityType', array('AllowComments' => '1', 'Name' => 'AboutUpdate', 'FullHeadline' => '%1$s updated %6$s profile.', 'ProfileHeadline' => '%1$s updated %6$s profile.'));
}
if ($SQL->getWhere('ActivityType', array('Name' => 'WallComment'))->numRows() == 0) {
    $SQL->insert('ActivityType', array('AllowComments' => '1', 'ShowIcon' => '1', 'Name' => 'WallComment', 'FullHeadline' => '%1$s wrote on %4$s %5$s.', 'ProfileHeadline' => '%1$s wrote:'));
}
if ($SQL->getWhere('ActivityType', array('Name' => 'PictureChange'))->numRows() == 0) {
    $SQL->insert('ActivityType', array('AllowComments' => '1', 'Name' => 'PictureChange', 'FullHeadline' => '%1$s changed %6$s profile picture.', 'ProfileHeadline' => '%1$s changed %6$s profile picture.'));
}
//if ($SQL->getWhere('ActivityType', array('Name' => 'RoleChange'))->numRows() == 0)
$SQL->replace('ActivityType', array('AllowComments' => '1', 'FullHeadline' => '%1$s changed %4$s permissions.', 'ProfileHeadline' => '%1$s changed %4$s permissions.', 'Notify' => '1'), array('Name' => 'RoleChange'), true);
if ($SQL->getWhere('ActivityType', array('Name' => 'ActivityComment'))->numRows() == 0) {
    $SQL->insert('ActivityType', array('AllowComments' => '0', 'ShowIcon' => '1', 'Name' => 'ActivityComment', 'FullHeadline' => '%1$s commented on %4$s %8$s.', 'ProfileHeadline' => '%1$s', 'RouteCode' => 'activity', 'Notify' => '1'));
}
if ($SQL->getWhere('ActivityType', array('Name' => 'Import'))->numRows() == 0) {
    $SQL->insert('ActivityType', array('AllowComments' => '0', 'Name' => 'Import', 'FullHeadline' => '%1$s imported data.', 'ProfileHeadline' => '%1$s imported data.', 'Notify' => '1', 'Public' => '0'));
}
//if ($SQL->getWhere('ActivityType', array('Name' => 'Banned'))->numRows() == 0)
$SQL->replace('ActivityType', array('AllowComments' => '0', 'FullHeadline' => '%1$s banned %3$s.', 'ProfileHeadline' => '%1$s banned %3$s.', 'Notify' => '0', 'Public' => '1'), array('Name' => 'Banned'), true);
//if ($SQL->getWhere('ActivityType', array('Name' => 'Unbanned'))->numRows() == 0)
$SQL->replace('ActivityType', array('AllowComments' => '0', 'FullHeadline' => '%1$s un-banned %3$s.', 'ProfileHeadline' => '%1$s un-banned %3$s.', 'Notify' => '0', 'Public' => '1'), array('Name' => 'Unbanned'), true);

// Applicant activity
if ($SQL->getWhere('ActivityType', array('Name' => 'Applicant'))->numRows() == 0) {
    $SQL->insert('ActivityType', array('AllowComments' => '0', 'Name' => 'Applicant', 'FullHeadline' => '%1$s applied for membership.', 'ProfileHeadline' => '%1$s applied for membership.', 'Notify' => '1', 'Public' => '0'));
}

$WallPostType = $SQL->getWhere('ActivityType', array('Name' => 'WallPost'))->firstRow(DATASET_TYPE_ARRAY);
if (!$WallPostType) {
    $WallPostTypeID = $SQL->insert('ActivityType', array('AllowComments' => '1', 'ShowIcon' => '1', 'Name' => 'WallPost', 'FullHeadline' => '%3$s wrote on %2$s %5$s.', 'ProfileHeadline' => '%3$s wrote:'));
    $WallCommentTypeID = $SQL->getWhere('ActivityType', array('Name' => 'WallComment'))->value('ActivityTypeID');

    // Update all old wall comments to wall posts.
    $SQL->update('Activity')
        ->set('ActivityTypeID', $WallPostTypeID)
        ->set('ActivityUserID', 'RegardingUserID', false)
        ->set('RegardingUserID', 'InsertUserID', false)
        ->where('ActivityTypeID', $WallCommentTypeID)
        ->where('RegardingUserID is not null')
        ->put();
}

$ActivityModel = new ActivityModel();
$ActivityModel->defineType('Default');
$ActivityModel->defineType('Registration');
$ActivityModel->defineType('Status');
$ActivityModel->defineType('Ban');

// Message Table
$Construct->table('Message')
    ->primaryKey('MessageID')
    ->column('Content', 'text')
    ->column('Format', 'varchar(20)', true)
    ->column('AllowDismiss', 'tinyint(1)', '1')
    ->column('Enabled', 'tinyint(1)', '1')
    ->column('Application', 'varchar(255)', true)
    ->column('Controller', 'varchar(255)', true)
    ->column('Method', 'varchar(255)', true)
    ->column('CategoryID', 'int', true)
    ->column('IncludeSubcategories', 'tinyint', '0')
    ->column('AssetTarget', 'varchar(20)', true)
    ->column('CssClass', 'varchar(20)', true)
    ->column('Sort', 'int', true)
    ->set($Explicit, $Drop);

$Prefix = $SQL->Database->DatabasePrefix;

if ($PhotoIDExists && !$PhotoExists) {
    $Construct->query("update {$Prefix}User u
   join {$Prefix}Photo p
      on u.PhotoID = p.PhotoID
   set u.Photo = p.Name");
}

if ($PhotoIDExists) {
    $Construct->table('User')->dropColumn('PhotoID');
}

$Construct->table('Tag');
$FullNameColumnExists = $Construct->columnExists('FullName');
$TagCategoryColumnExists = $Construct->columnExists('CategoryID');

// This is a fix for erroneous unique constraint.
if ($Construct->tableExists('Tag') && $TagCategoryColumnExists) {
    $Db = Gdn::database();
    $Px = Gdn::database()->DatabasePrefix;

    $DupTags = Gdn::sql()
        ->select('Name, CategoryID')
        ->select('TagID', 'min', 'TagID')
        ->select('TagID', 'count', 'CountTags')
        ->from('Tag')
        ->groupBy('Name')
        ->groupBy('CategoryID')
        ->having('CountTags >', 1)
        ->get()->resultArray();

    foreach ($DupTags as $Row) {
        $Name = $Row['Name'];
        $CategoryID = $Row['CategoryID'];
        $TagID = $Row['TagID'];
        // Get the tags that need to be deleted.
        $DeleteTags = Gdn::sql()->getWhere('Tag', array('Name' => $Name, 'CategoryID' => $CategoryID, 'TagID <> ' => $TagID))->resultArray();
        foreach ($DeleteTags as $DRow) {
            // Update all of the discussions to the new tag.
            Gdn::sql()->options('Ignore', true)->put(
                'TagDiscussion',
                array('TagID' => $TagID),
                array('TagID' => $DRow['TagID'])
            );

            // Delete the tag.
            Gdn::sql()->delete('Tag', array('TagID' => $DRow['TagID']));
        }
    }
}

$Construct->table('Tag')
    ->primaryKey('TagID')
    ->column('Name', 'varchar(100)', false, 'unique')
    ->column('FullName', 'varchar(100)', !$FullNameColumnExists, 'index')
    ->column('Type', 'varchar(20)', '', 'index')
    ->column('ParentTagID', 'int', true, 'key')
    ->column('InsertUserID', 'int', true, 'key')
    ->column('DateInserted', 'datetime')
    ->column('CategoryID', 'int', -1, 'unique')
    ->Engine('InnoDB')
    ->set($Explicit, $Drop);

if (!$FullNameColumnExists) {
    Gdn::sql()->update('Tag')
        ->set('FullName', 'Name', false, false)
        ->put();

    $Construct->table('Tag')
        ->column('FullName', 'varchar(255)', false, 'index')
        ->set();
}

$Construct->table('Log')
    ->primaryKey('LogID')
    ->column('Operation', array('Delete', 'Edit', 'Spam', 'Moderate', 'Pending', 'Ban', 'Error'), false, 'index')
    ->column('RecordType', array('Discussion', 'Comment', 'User', 'Registration', 'Activity', 'ActivityComment', 'Configuration', 'Group'), false, 'index')
    ->column('TransactionLogID', 'int', null)
    ->column('RecordID', 'int', null, 'index')
    ->column('RecordUserID', 'int', null, 'index')// user responsible for the record; indexed for user deletion
    ->column('RecordDate', 'datetime')
    ->column('RecordIPAddress', 'varchar(15)', null, 'index')
    ->column('InsertUserID', 'int')// user that put record in the log
    ->column('DateInserted', 'datetime', false, 'index')// date item added to log
    ->column('InsertIPAddress', 'varchar(15)', null)
    ->column('OtherUserIDs', 'varchar(255)', null)
    ->column('DateUpdated', 'datetime', null)
    ->column('ParentRecordID', 'int', null, 'index')
    ->column('CategoryID', 'int', null, 'key')
    ->column('Data', 'mediumtext', null)// the data from the record.
    ->column('CountGroup', 'int', null)
    ->engine('InnoDB')
    ->set($Explicit, $Drop);

$Construct->table('Regarding')
    ->primaryKey('RegardingID')
    ->column('Type', 'varchar(100)', false, 'key')
    ->column('InsertUserID', 'int', false)
    ->column('DateInserted', 'datetime', false)
    ->column('ForeignType', 'varchar(32)', false)
    ->column('ForeignID', 'int(11)', false)
    ->column('OriginalContent', 'text', true)
    ->column('ParentType', 'varchar(32)', true)
    ->column('ParentID', 'int(11)', true)
    ->column('ForeignURL', 'varchar(255)', true)
    ->column('Comment', 'text', false)
    ->column('Reports', 'int(11)', true)
    ->engine('InnoDB')
    ->set($Explicit, $Drop);

$Construct->table('Ban')
    ->primaryKey('BanID')
    ->column('BanType', array('IPAddress', 'Name', 'Email'), false, 'unique')
    ->column('BanValue', 'varchar(50)', false, 'unique')
    ->column('Notes', 'varchar(255)', null)
    ->column('CountUsers', 'uint', 0)
    ->column('CountBlockedRegistrations', 'uint', 0)
    ->column('InsertUserID', 'int')
    ->column('DateInserted', 'datetime')
    ->column('InsertIPAddress', 'varchar(15)', true)
    ->column('UpdateUserID', 'int', true)
    ->column('DateUpdated', 'datetime', true)
    ->column('UpdateIPAddress', 'varchar(15)', true)
    ->engine('InnoDB')
    ->set($Explicit, $Drop);

$Construct->table('Spammer')
    ->column('UserID', 'int', false, 'primary')
    ->column('CountSpam', 'usmallint', 0)
    ->column('CountDeletedSpam', 'usmallint', 0)
    ->set($Explicit, $Drop);

$Construct
    ->table('Media')
    ->primaryKey('MediaID')
    ->column('Name', 'varchar(255)')
    ->column('Path', 'varchar(255)')
    ->column('Type', 'varchar(128)')
    ->column('Size', 'int(11)')
    ->column('InsertUserID', 'int(11)')
    ->column('DateInserted', 'datetime')
    ->column('ForeignID', 'int(11)', true, 'index.Foreign')
    ->column('ForeignTable', 'varchar(24)', true, 'index.Foreign')
    ->column('ImageWidth', 'usmallint', null)
    ->column('ImageHeight', 'usmallint', null)
//   ->column('StorageMethod', 'varchar(24)')
    ->column('ThumbWidth', 'usmallint', null)
    ->column('ThumbHeight', 'usmallint', null)
    ->column('ThumbPath', 'varchar(255)', null)
    ->set(false, false);

// Merge backup.
$Construct
    ->table('UserMerge')
    ->primaryKey('MergeID')
    ->column('OldUserID', 'int', false, 'key')
    ->column('NewUserID', 'int', false, 'key')
    ->column('DateInserted', 'datetime')
    ->column('InsertUserID', 'int')
    ->column('DateUpdated', 'datetime', true)
    ->column('UpdateUserID', 'int', true)
    ->column('Attributes', 'text', true)
    ->set();

$Construct
    ->table('UserMergeItem')
    ->column('MergeID', 'int', false, 'key')
    ->column('Table', 'varchar(30)')
    ->column('Column', 'varchar(30)')
    ->column('RecordID', 'int')
    ->column('OldUserID', 'int')
    ->column('NewUserID', 'int')
    ->set();

$Construct
    ->table('Attachment')
    ->primaryKey('AttachmentID')
    ->column('Type', 'varchar(64)')// ex: zendesk-case, vendor-item
    ->column('ForeignID', 'varchar(50)', false, 'index')// ex: d-123 for DiscussionID 123, u-555 for UserID 555
    ->column('ForeignUserID', 'int', false, 'key')// the user id of the record we are attached to (de-normalization)
    ->column('Source', 'varchar(64)')// ex: Zendesk, Vendor
    ->column('SourceID', 'varchar(32)')// ex: 1
    ->column('SourceURL', 'varchar(255)')
    ->column('Attributes', 'text', true)
    ->column('DateInserted', 'datetime')
    ->column('InsertUserID', 'int', false, 'key')
    ->column('InsertIPAddress', 'varchar(64)')
    ->column('DateUpdated', 'datetime', true)
    ->column('UpdateUserID', 'int', true)
    ->column('UpdateIPAddress', 'varchar(15)', true)
    ->set($Explicit, $Drop);

// Save the current input formatter to the user's config.
// This will allow us to change the default later and grandfather existing forums in.
saveToConfig('Garden.InputFormatter', c('Garden.InputFormatter'));

// Make sure the default locale is in its canonical form.
$currentLocale = c('Garden.Locale');
$canonicalLocale = Gdn_Locale::canonicalize($currentLocale);
if ($currentLocale !== $canonicalLocale) {
    saveToConfig('Garden.Locale', $canonicalLocale);
}

// We need to undo cleditor's bad behavior for our reformed users.
// If you still need to manipulate this, do it in memory instead (SAVE = false).
if (!c('Garden.Html.SafeStyles')) {
    removeFromConfig('Garden.Html.SafeStyles');
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
