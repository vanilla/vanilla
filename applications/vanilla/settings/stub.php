<?php if (!defined('APPLICATION')) exit();
/**
 * Vanilla stub content for a new forum.
 *
 * Called by VanillaHooks::Setup() to insert stub content upon enabling app.
 * @package Vanilla
 */

// Only do this once, ever.
if (!Gdn::SQL()->Get('Discussion', '', 'asc', 1)->FirstRow(DATASET_TYPE_ARRAY)) {

   // Prep default content
   $Now = Gdn_Format::ToDateTime();
   $DiscussionBody = "There&rsquo;s nothing sweeter than a fresh new forum, ready to welcome your community. A Vanilla Forum has all the bits and pieces you need to build an awesome discussion platform customized to your needs. Here&rsquo;s a few tips:
<ul>
   <li>Use the <a href=\"/dashboard/settings/gettingstarted\">Getting Started</a> list in the Dashboard to configure your site.</li>
   <li>Don&rsquo;t use too many categories. We recommend 3-8. Keep it simple!</li>
   <li>&ldquo;Announce&rdquo; a discussion (click the gear) to stick to the top of the list, and &ldquo;Close&rdquo; it to stop further comments.</li>
   <li>Use &ldquo;Sink&rdquo; to take attention away from a discussion. New comments will no longer bring it back to the top of the list.</li>
   <li>Bookmark a discussion (click the star) to get notifications for new comments. You can edit notification settings from your profile.</li>
</ul>
Go ahead and edit or delete this discussion, then spread the word to get this place cooking. Cheers!";
   $Discussion = array(
      'Name' => T('StubDiscussionTitle', "BAM! You&rsquo;ve got a sweet forum"),
      'Body' => T('StubDiscussionBody', $DiscussionBody),
      'Format' => 'Html',
      'CategoryID' => val('CategoryID', CategoryModel::DefaultCategory()),
      'ForeignID' => 'stub',
      'InsertUserID' => Gdn::UserModel()->GetSystemUserID(),
      'DateInserted' => $Now,
      'DateLastComment' => $Now,
      'LastCommentUserID' => Gdn::UserModel()->GetSystemUserID(),
      'CountComments' => 1
   );

   $CommentBody = "This is the first comment on your site and it&rsquo;s an important one.
\nDon&rsquo;t see your must-have feature? We keep Vanilla nice and simple by default. Use <b>addons</b> to get the special sauce your community needs.
\nNot sure which addons to enable? Our favorites are Button Bar and Tagging. They&rsquo;re almost always a great start.";

   $Comment = array(
      'Body' => T('StubCommentBody', $CommentBody),
      'Format' => 'Html',
      'InsertUserID' => Gdn::UserModel()->GetSystemUserID(),
      'DateInserted' => $Now
   );
   $Discussion['Comments'] = array($Comment);
   $Discussions = array($Discussion);

   // Get wall post type ID
   $FirstUser = Gdn::UserModel()->GetWhere('Admin', 1)->FirstRow(DATASET_TYPE_ARRAY);
   $WallCommentTypeID = Gdn::SQL()->GetWhere('ActivityType', array('Name' => 'WallPost'))->Value('ActivityTypeID');
   $WallBody = "Ping! An activity post is a public way to talk at someone. When you update your status here, it posts it on your activity feed.";
   $Activity = array(
      'Story' => T('StubWallBody', $WallBody),
      'Format' => 'Html',
      'HeadlineFormat' => '{RegardingUserID,you} &rarr; {ActivityUserID,you}',
      'NotifyUserID' => -1,
      'ActivityUserID' => $FirstUser['UserID'],
      'RegardingUserID' => Gdn::UserModel()->GetSystemUserID(),
      'ActivityTypeID' => $WallCommentTypeID,
      'InsertUserID' => Gdn::UserModel()->GetSystemUserID(),
      'DateInserted' => $Now,
      'DateUpdated' => $Now
   );
   $Activities = array($Activity);

   // Fire stub event for plugins.
   $this->EventArguments['Discussions'] = &$Discussions;
   $this->EventArguments['Activities'] = &$Activities;
   $this->FireEvent('StubContent');

   // Discussions.
   $DiscussionModel = new DiscussionModel();
   foreach ($Discussions as $Discussion) {
      // Insert discussion.
      $DiscussionID = Gdn::SQL()->Options('Ignore', true)->Insert('Discussion', $Discussion);
      $DiscussionModel->UpdateDiscussionCount($Discussion['CategoryID']);

      // Insert comments.
      $Comments = val('Comments', $Discussion);
      $CommentCount = 0;
      if (is_array($Comments) && count($Comments)) {
         foreach ($Comments as $Comment) {
            $Comment['DiscussionID'] = $DiscussionID;
            $CommentID = Gdn::SQL()->Insert('Comment', $Comment);
            Gdn::SQL()->Update('Discussion')
               ->Set('LastCommentID', $CommentID)
               ->Set('CountComments', 'CountComments + 1', FALSE)
               ->Where('DiscussionID', $DiscussionID)
               ->Put();
            $CommentCount++;
         }
      }
   }

   // Acitivities.
   foreach ($Activities as $Activity) {
      Gdn::SQL()->Insert('Activity', $Activity);
   }
}
