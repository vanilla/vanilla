<?php if (!defined('APPLICATION')) exit();

function WriteActivity($Activity, &$Sender, &$Session) {
   $Activity = (object)$Activity;
   // If this was a status update or a wall comment, don't bother with activity strings
   $ActivityType = explode(' ', $Activity->ActivityType); // Make sure you strip out any extra css classes munged in here
   $ActivityType = $ActivityType[0];
   $Author = UserBuilder($Activity, 'Activity');
   $PhotoAnchor = UserPhoto($Author, 'Photo');
   $CssClass = 'Item Activity '.$ActivityType;
   if ($PhotoAnchor != '')
      $CssClass .= ' HasPhoto';
   if (in_array($ActivityType, array('WallComment', 'WallPost', 'AboutUpdate')))
      $CssClass .= ' Condensed';
      
   $Title = '';
   $Excerpt = $Activity->Story;
   if (!in_array($ActivityType, array('WallComment', 'WallPost', 'AboutUpdate'))) {
      $Title = '<div class="Title">'.Gdn_Format::ActivityHeadline($Activity, $Sender->ProfileUserID).'</div>';
   } else if ($ActivityType == 'WallPost') {
      $RegardingUser = UserBuilder($Activity, 'Regarding');
      $PhotoAnchor = UserPhoto($RegardingUser);
      $Title = '<div class="Title">'
         .UserAnchor($RegardingUser, 'Name')
         .' <span>&rarr;</span> '
         .UserAnchor($Author, 'Title Name')
         .'</div>';
      $Excerpt = Gdn_Format::Display($Excerpt);
   } else {
      $Title = UserAnchor($Author, 'Title Name');
      $Excerpt = Gdn_Format::Display($Excerpt);
   }
   $Sender->EventArguments['Activity'] = &$Activity;
   $Sender->EventArguments['CssClass'] = &$CssClass;
   $Sender->FireEvent('BeforeActivity');
   ?>
<li id="Activity_<?php echo $Activity->ActivityID; ?>" class="<?php echo $CssClass; ?>">
   <?php
   if (
      $Session->IsValid()
      && ($Session->UserID == $Activity->InsertUserID
         || $Session->CheckPermission('Garden.Activity.Delete'))
      )
      echo '<div class="Options">'.Anchor(T('Activity.Delete', 'Delete'), 'dashboard/activity/delete/'.$Activity->ActivityID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'Delete').'</div>';

   if ($PhotoAnchor != '') {
   ?>
   <div class="Author Photo"><?php echo $PhotoAnchor; ?></div>
   <?php } ?>
   <div class="ItemContent Activity">
      <?php echo $Title; ?>
      <div class="Excerpt"><?php echo $Excerpt; ?></div>
      <div class="Meta">
         <span class="MItem DateCreated"><?php echo Gdn_Format::Date($Activity->DateInserted); ?></span>
         <?php
         if ($Activity->AllowComments == '1' && $Session->CheckPermission('Garden.Profiles.Edit'))
            echo '<span class="MItem AddComment">'.Anchor(T('Activity.Comment', 'Comment'), '#CommentForm_'.$Activity->ActivityID, 'CommentOption').'</span>';
         
         $Sender->FireEvent('AfterMeta');
         ?>
      </div>
   </div>
   <?php
   $Comments = $Activity->Comments;
   if (count($Comments) > 0) {
      echo '<ul class="DataList ActivityComments">';
      foreach ($Comments as $Comment) {
         WriteActivityComment($Comment, $Sender, $Session);
      }
   } else {
      echo '<ul class="DataList ActivityComments Hidden">';
   }
   
   if ($Session->CheckPermission('Garden.Profiles.Edit')):
      ?>
      <li class="CommentForm">
      <?php
         echo Anchor(T('Write a comment'), '/dashboard/activity/comment/'.$Activity->ActivityID, 'CommentLink');
         $CommentForm = Gdn::Factory('Form');
         $CommentForm->SetModel($Sender->ActivityModel);
         $CommentForm->AddHidden('ActivityID', $Activity->ActivityID);
         $CommentForm->AddHidden('Return', Gdn_Url::Request());
         echo $CommentForm->Open(array('action' => Url('/dashboard/activity/comment'), 'class' => 'Hidden'));
         echo '<div class="TextBoxWrapper">'.$CommentForm->TextBox('Body', array('MultiLine' => TRUE, 'value' => '')).'</div>';
         echo $CommentForm->Close('Comment');
      ?></li>
   <?php 
   endif;
   
   echo '</ul>';
?>
</li>
<?php
}

function WriteActivityComment($Comment, &$Sender, &$Session) {
   $Author = UserBuilder($Comment, 'Insert');
   $PhotoAnchor = UserPhoto($Author, 'Photo');
   $CssClass = 'Item ActivityComment Condensed ActivityComment';
   if ($PhotoAnchor != '')
      $CssClass .= ' HasPhoto';
   
?>
<li id="ActivityComment_<?php echo $Comment['ActivityCommentID']; ?>" class="<?php echo $CssClass; ?>">
   <?php if ($PhotoAnchor != '') { ?>
   <div class="Author Photo"><?php echo $PhotoAnchor; ?></div>
   <?php } ?>
   <div class="ItemContent ActivityComment">
      <?php echo UserAnchor($Author, 'Title Name'); ?>
      <div class="Excerpt"><?php echo Gdn_Format::To($Comment['Body'], $Comment['Format']); ?></div>
      <div class="Meta">
         <span class="DateCreated"><?php echo Gdn_Format::Date($Comment['DateInserted'], 'html'); ?></span>
         <?php
            if ($Session->UserID == $Comment['InsertUserID'] || $Session->CheckPermission('Garden.Activity.Delete'))
               echo Anchor(T('Delete'), "dashboard/activity/deletecomment?id={$Comment['ActivityCommentID']}&tk=".$Session->TransientKey().'&target='.urlencode(Gdn_Url::Request()), 'DeleteComment');
         ?>
      </div>
   </div>
</li>
<?php
}