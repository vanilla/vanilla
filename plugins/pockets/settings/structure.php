<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

if (!defined('APPLICATION')) {
    exit();
}

if (!isset($Drop)) {
    $Drop = false;
}

if (!isset($Explicit)) {
    $Explicit = false;
}

$Sql = Gdn::sql();
$St = Gdn::structure();



// Define the groups table.
$St->table('Group');
$GroupExists = $St->tableExists();
$CountDiscussionsExists = $St->columnExists('CountDiscussions');
$GroupPrivacyExists = $St->columnExists('Privacy');

$St
    ->primaryKey('GroupID')
    ->column('Name', 'varchar(150)', false, 'unique')
    ->column('Description', 'text')
    ->column('Format', 'varchar(10)', true)
    ->column('CategoryID', 'int', false, 'key')
    ->column('Icon', 'varchar(255)', true)
    ->column('Banner', 'varchar(255)', true)
    ->column('Privacy', ['Public', 'Private', 'Secret'], 'Public')
    ->column('Registration', ['Public', 'Approval', 'Invite'], true) // deprecated
    ->column('Visibility', ['Public', 'Members'], true) // deprecated
    ->column('CountMembers', 'uint', '0')
    ->column('CountDiscussions', 'uint', '0')
    ->column('DateLastComment', 'datetime', true)
    ->column('LastCommentID', 'int', null)
    ->column('LastDiscussionID', 'int', null)
    ->column('DateInserted', 'datetime')
    ->column('InsertUserID', 'int')
    ->column('DateUpdated', 'datetime', true)
    ->column('UpdateUserID', 'int', true)
    ->column('Attributes', 'text', true)
    ->set($Explicit, $Drop);

if ($GroupExists && !$GroupPrivacyExists) {
    $Sql->put('Group', ['Privacy' => 'Private']);
    $Sql->put('Group', ['Privacy' => 'Public'], ['Registration' => 'Public', 'Visibility' => 'Public']);
}

$St->table('UserGroup')
    ->primaryKey('UserGroupID')
    ->column('GroupID', 'int', false, 'unique')
    ->column('UserID', 'int', false, ['unique', 'key'])
    ->column('DateInserted', 'datetime')
    ->column('InsertUserID', 'int')
    ->column('Role', ['Leader', 'Member'])
    ->set($Explicit, $Drop);

$St->table('GroupApplicant')
    ->primaryKey('GroupApplicantID')
    ->column('GroupID', 'int', false, 'unique')
    ->column('UserID', 'int', false, ['unique', 'key'])
    ->column('Type', ['Application', 'Invitation', 'Denied', 'Banned'])
    ->column('Reason', 'varchar(200)', true) // reason for wanting to join.
    ->column('DateInserted', 'datetime')
    ->column('InsertUserID', 'int')
    ->column('DateUpdated', 'datetime', true)
    ->column('UpdateUserID', 'int', true)
    ->set($Explicit, $Drop);

if ($St->tableExists('Category')) {
    $St->table('Category');
    $AllowGroupsExists = $St->columnExists('AllowGroups');
    $canDeleteExists = $St->columnExists('CanDelete');
    $St->table('Category')
        ->column('AllowGroups', 'tinyint', '0')
        ->set();
    if (!$AllowGroupsExists) {
        // Create a category for groups.
        $Model = new CategoryModel();
        $Row = CategoryModel::categories('social-groups');
        if ($Row) {
            $Model->setField($Row['CategoryID'], 'AllowGroups', 1);
            // Backwards compat for a new column.
            if ($canDeleteExists) {
                $Model->setField($Row['CategoryID'], 'CanDelete', 0);
            }
        } else {
            $Row = [
                'Name' => 'Social Groups',
                'UrlCode' => 'social-groups',
                'HideAllDiscussions' => 1,
                'DisplayAs' => 'Discussions',
                'AllowDiscussions' => 1,
                'AllowGroups' => 1,
                'Sort' => 1000,
            ];

            $permissions = [];
            /** @var PermissionModel $permissionModel */
            $permissionModel = Gdn::getContainer()->get(PermissionModel::class);
            /** @var RoleModel $roleModel */
            $roleModel = Gdn::getContainer()->get(RoleModel::class);
            $defaultPermissions = $permissionModel->getDefaults();

            // Upgrade existing category permissions to hide the Social Groups from everyone.
            $roles = $roleModel->get();
            foreach ($roles as $role) {
                $roleID = val('RoleID', $role);
                $roleType = val('Type', $role);
                $typeDefaults = $defaultPermissions[$roleType] ?? null;
                if ($typeDefaults) {
                    $categoryDefaults = $typeDefaults['Category:-1'] ?? null;
                    if ($categoryDefaults) {
                        $permissionRow = $categoryDefaults;
                        $permissionRow['RoleID'] = $roleID;
                        $permissionRow['Vanilla.Discussions.View'] = 0;
                        $permissions[] = $permissionRow;
                    }
                }
            }
            $Row['Permissions'] = $permissions;

            // Backwards compat for a new column.
            if ($canDeleteExists) {
                $Row['CanDelete'] = 0;
            }

            $Model->save($Row);
        }
    }
}

if ($St->tableExists('Discussion')) {
    $St->table('Discussion')
        ->column('GroupID', 'int', true, 'key')
        ->set();
}

if (!$CountDiscussionsExists) {
    $GroupModel = new GroupModel();
    $GroupModel->counts('CountDiscussions');
}

$St->table('Event');

$timeZoneExists = $St->columnExists('Timezone');

$St->primaryKey('EventID')
    ->column('Name', 'varchar(255)')
    ->column('Body', 'text')
    ->column('Format', 'varchar(10)', true)
    ->column('ParentRecordType', 'varchar(25)', true, 'index.Event')
    ->column('ParentRecordID', 'int', true, 'index.Event')
    ->column('DateStarts', 'datetime', false, 'index.DateStart')
    ->column('DateEnds', 'datetime', true, 'index.DateEnd')
    ->column('AllDayEvent', 'tinyint', '0')
    ->column('Location', 'varchar(255)', true)
    ->column('DateInserted', 'datetime')
    ->column('InsertUserID', 'int') // organizer
    ->column('DateUpdated', 'datetime', true)
    ->column('UpdateUserID', 'int', true)
    ->column('GroupID', 'int', true, 'key') // eventually make events stand-alone.
    ->set($Explicit, $Drop);

if ($timeZoneExists) {
    $St->table('Event')->dropColumn('Timezone');
}

$St->table('UserEvent')
    ->column('EventID', 'int', false, 'primary')
    ->column('UserID', 'int', false, ['primary', 'key'])
    ->column('DateInserted', 'datetime')
    ->column('Attending', ['Yes', 'No', 'Maybe', 'Invited'], 'Invited')
    ->set($Explicit, $Drop);

// Make sure the activity table has an index that the event wall can use.
$St->table('Activity')
    ->column('RecordType', 'varchar(20)', true, 'index.Record')
    ->column('RecordID', 'int', true, 'index.Record')
    ->set();

$ActivityModel = new ActivityModel();
$ActivityModel->defineType('Groups');
$ActivityModel->defineType('Events');

// Added for backwards compatibility
// Group announcements should always (in theory) be set to Announce = 2
// Old group announcements and/or moved announcements (no GroupID) into Groups (perhaps there's more cases) are set to Announce = 1
// Running utility update will fix this erroneous data and prevent group announcements from showing in recent discussions
if ($St->tableExists('Discussion')) {
    $groupModel = new GroupModel();
    $groupCategoryIDs = $groupModel->getGroupCategoryIDs();
    if ($groupCategoryIDs) {
        $updateDiscussions =  Gdn::sql()
            ->select('DiscussionID')
            ->where('Announce', 1)
            ->beginWhereGroup()
            ->where('GroupID is not null', '')
            ->orWhereIn('CategoryID', $groupCategoryIDs)
            ->endWhereGroup()
            ->get(' Discussion', '', '', 1)
            ->resultObject();
        if ($updateDiscussions) {
            $result = Gdn::sql()
                ->update('Discussion')
                ->set('Announce', 2)
                ->where('Announce', 1)
                ->beginWhereGroup()
                ->where('GroupID is not null', '')
                ->orWhereIn('CategoryID', $groupCategoryIDs)
                ->endWhereGroup()
                ->put();
        }
    }
}

if ($St->tableExists('Event')) {
    Gdn::sql()
        ->update('Event e')
        ->set('ParentRecordID', 'e.GroupID', false)
        ->set('ParentRecordType', 'group')
        ->where('ParentRecordID is null')
        ->put();

    Gdn::sql()
        ->update('Event')
        ->set('DateEnds', 'DateStarts + INTERVAL 1 DAY', false)
        ->set('AllDayEvent', 1)
        ->where('DateEnds is NULL')
        ->put();
}
