<?php if (!defined('APPLICATION')) exit();
/**
 * Vanilla stub content for a new forum.
 *
 * Called by VanillaHooks::Setup() to insert stub content upon enabling app.
 * @package Vanilla
 */

// Only do this once, ever.
if (!$Drop)
   return;

$SQL = Gdn::Database()->SQL();
$DiscussionModel = new DiscussionModel();

// Prep default content
$DiscussionTitle = "BAM! You&rsquo;ve got a sweet forum";
$DiscussionBody = "There&rsquo;s nothing sweeter than a fresh new forum, ready to welcome your community. A Vanilla Forum has all the bits and pieces you need to build an awesome discussion platform customized to your needs. Here&rsquo;s a few tips:
<ul>
<li>Use the <a href=\"/dashboard/settings/gettingstarted\">Getting Started</a> list in the Dashboard to configure your site.</li>
<li>Don&rsquo;t use too many categories. We recommend 3-8. Keep it simple!
<li>&ldquo;Announce&rdquo; a discussion (click the gear) to stick to the top of the list, and &ldquo;Close&rdquo; it to stop further comments.</li>
<li>Use &ldquo;Sink&rdquo; to take attention away from a discussion. New comments will no longer bring it back to the top of the list.</li>
<li>Bookmark a discussion (click the star) to get notifications for new comments. You can edit notification settings from your profile.</li>
</ul>
Go ahead and edit or delete this discussion, then spread the word to get this place cooking. Cheers!";
$CommentBody = "This is the first comment on your site and it&rsquo;s an important one. 

Don&rsquo;t see your must-have feature? We keep Vanilla nice and simple by default. Use <b>addons</b> to get the special sauce your community needs.

Not sure which addons to enable? Our favorites are Button Bar and Tagging. They&rsquo;re almost always a great start.";
$WallBody = "Ping! An activity post is a public way to talk at someone. When you update your status here, it posts it on your activity feed.";

// Prep content meta data
$SystemUserID = Gdn::UserModel()->GetSystemUserID();
$TargetUserID = Gdn::Session()->UserID;
$Now = Gdn_Format::ToDateTime();
$CategoryID = GetValue('CategoryID', CategoryModel::DefaultCategory());

// Get wall post type ID 
$WallCommentTypeID = $SQL->GetWhere('ActivityType', array('Name' => 'WallPost'))->Value('ActivityTypeID');

// Insert first discussion & comment
$DiscussionID = $SQL->Insert('Discussion', array(
   'Name' => T('StubDiscussionTitle', $DiscussionTitle),
   'Body' => T('StubDiscussionBody', $DiscussionBody),
   'Format' => 'Html',
   'CategoryID' => $CategoryID,
   'ForeignID' => 'stub',
   'InsertUserID' => $SystemUserID,
   'DateInserted' => $Now,
   'DateLastComment' => $Now,
   'LastCommentUserID' => $SystemUserID,
   'CountComments' => 1
));
$CommentID = $SQL->Insert('Comment', array(
   'DiscussionID' => $DiscussionID,
   'Body' => T('StubCommentBody', $CommentBody),
   'Format' => 'Html',
   'InsertUserID' => $SystemUserID,
   'DateInserted' => $Now
));
$SQL->Update('Discussion')
   ->Set('LastCommentID', $CommentID)
   ->Where('DiscussionID', $DiscussionID)
   ->Put();
$DiscussionModel->UpdateDiscussionCount($CategoryID);
   
// Insert first wall post
$SQL->Insert('Activity', array(
   'Story' => T('StubWallBody', $WallBody),
   'Format' => 'Html',
   'HeadlineFormat' => '{RegardingUserID,you} &rarr; {ActivityUserID,you}',
   'NotifyUserID' => -1,
   'ActivityUserID' => $TargetUserID,
   'RegardingUserID' => $SystemUserID,
   'ActivityTypeID' => $WallCommentTypeID,
   'InsertUserID' => $SystemUserID,
   'DateInserted' => $Now,
   'DateUpdated' => $Now
));
