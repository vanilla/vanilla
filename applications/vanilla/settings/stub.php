<?php if (!defined('APPLICATION')) exit();
/**
 * Vanilla stub content for a new forum.
 *
 * Called by VanillaHooks::setup() to insert stub content upon enabling app.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @since 2.0
 * @package Vanilla
 */

$SQL = Gdn::database()->sql();

// Only do this once, ever.
$Row = $SQL->get('Discussion', '', 'asc', 1)->firstRow(DATASET_TYPE_ARRAY);
if ($Row) {
    return;
}

$WallBody = "Ping! An activity post is a public way to talk at someone. When you update your status here, it posts it on your activity feed.";

// Prep content meta data
$SystemUserID = Gdn::userModel()->getSystemUserID();
$TargetUserID = Gdn::session()->UserID;
$Now = Gdn_Format::toDateTime();
$CategoryID = val('CategoryID', CategoryModel::defaultCategory());

// Get wall post type ID
$WallCommentTypeID = $SQL->getWhere('ActivityType', ['Name' => 'WallPost'])->value('ActivityTypeID');

// Insert first wall post
$SQL->insert('Activity', [
    'Story' => t('StubWallBody', $WallBody),
    'Format' => 'Html',
    'HeadlineFormat' => '{RegardingUserID,you} &rarr; {ActivityUserID,you}',
    'NotifyUserID' => -1,
    'ActivityUserID' => $TargetUserID,
    'RegardingUserID' => $SystemUserID,
    'ActivityTypeID' => $WallCommentTypeID,
    'InsertUserID' => $SystemUserID,
    'DateInserted' => $Now,
    'DateUpdated' => $Now
]);
