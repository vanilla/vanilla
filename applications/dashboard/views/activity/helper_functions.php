<?php if (!defined('APPLICATION')) exit();

function WriteActivity($Activity, &$Sender, &$Session) {
   $Activity = (object)$Activity;
   // If this was a status update or a wall comment, don't bother with activity strings
   $ActivityType = explode(' ', $Activity->ActivityType); // Make sure you strip out any extra css classes munged in here
   $ActivityType = $ActivityType[0];
   $Author = UserBuilder($Activity, 'Activity');
   $PhotoAnchor = '';
   
   if ($Activity->Photo) {
      $PhotoAnchor = Anchor(
         Img($Activity->Photo, array('class' => 'ProfilePhoto ProfilePhotoMedium')),
         $Activity->PhotoUrl, 'PhotoWrap');
   }
   
   $CssClass = 'Item Activity Activity-'.$ActivityType;
   if ($PhotoAnchor != '')
      $CssClass .= ' HasPhoto';
   
   $Format = GetValue('Format', $Activity);
      
   $Title = '';
   $Excerpt = $Activity->Story;
   if ($Format) {
      $Excerpt = Gdn_Format::To($Excerpt, $Format);
   }
   
   if (!in_array($ActivityType, array('WallComment', 'WallPost', 'AboutUpdate'))) {
      $Title = '<div class="Title">'.GetValue('Headline', $Activity).'</div>';
   } else if ($ActivityType == 'WallPost') {
      $RegardingUser = UserBuilder($Activity, 'Regarding');
      $PhotoAnchor = UserPhoto($RegardingUser);
      $Title = '<div class="Title">'
         .UserAnchor($RegardingUser, 'Name')
         .' <span>&rarr;</span> '
         .UserAnchor($Author, 'Name')
         .'</div>';
      
      if (!$Format)
         $Excerpt = Gdn_Format::Display($Excerpt);
   } else {
      $Title = UserAnchor($Author, 'Name');
      if (!$Format)
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
      echo '<div class="Options">'.Anchor('×', 'dashboard/activity/delete/'.$Activity->ActivityID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'Delete').'</div>';

   if ($PhotoAnchor != '') {
   ?>
   <div class="Author Photo"><?php echo $PhotoAnchor; ?></div>
   <?php } ?>
   <div class="ItemContent Activity">
      <?php echo $Title; ?>
      <?php echo WrapIf($Excerpt, 'div', array('class' => 'Excerpt')); ?>
      <?php 
      $Sender->EventArguments['Activity'] = $Activity;
      $Sender->FireAs('ActivityController')->FireEvent('AfterActivityBody');

      // Reactions stub
      if (in_array(GetValue('ActivityType', $Activity), array('Status', 'WallPost')))
         WriteReactions($Activity);
      ?>
      <div class="Meta">
         <span class="MItem DateCreated"><?php echo Gdn_Format::Date($Activity->DateUpdated); ?></span>
         <?php
         $SharedString = FALSE;
         $ID = GetValue('SharedNotifyUserID', $Activity->Data);
         if (!$ID)
            $ID = GetValue('CommentNotifyUserID', $Activity->Data);
         
         if ($ID)
            $SharedString = FormatString(T('Comments are between {UserID,you}.'), array('UserID' => array($Activity->NotifyUserID, $ID))); 
         
         $AllowComments = $Activity->NotifyUserID < 0 || $SharedString;
         
         
         
         if ($AllowComments && $Session->CheckPermission('Garden.Profiles.Edit'))
            echo '<span class="MItem AddComment">'
               .Anchor(T('Activity.Comment', 'Comment'), '#CommentForm_'.$Activity->ActivityID, 'CommentOption');
         
            if ($SharedString) {
               echo ' <span class="MItem"><i>'.$SharedString.'</i></span>';
            }
         
            echo '</span>';
         
         $Sender->FireEvent('AfterMeta');
         ?>
      </div>
   </div>
   <?php
   $Comments = GetValue('Comments', $Activity, array());
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
         
         echo '<div class="Buttons">';
         echo $CommentForm->Button('Comment', array('class' => 'Button Primary'));
         echo '</div>';
         
         echo $CommentForm->Close();
      ?></li>
   <?php 
   endif;
   
   echo '</ul>';
?>
</li>
<?php
}

if (!function_exists('WriteActivityComment')):

function WriteActivityComment($Comment, &$Sender, &$Session) {
   $Author = UserBuilder($Comment, 'Insert');
   $PhotoAnchor = UserPhoto($Author, 'Photo');
   $CssClass = 'Item ActivityComment ActivityComment';
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

endif;

function WriteActivityTabs() {
   $Sender = Gdn::Controller();
   $ModPermission = Gdn::Session()->CheckPermission('Garden.Moderation.Manage');
   $AdminPermission = Gdn::Session()->CheckPermission('Garden.Settings.Manage');
   
   if (!$ModPermission && !$AdminPermission)
      return;
?>
   <div class="Tabs ActivityTabs">
      <ul>
         <li <?php if ($Sender->Data('Filter') == 'public') echo 'class="Active"'; ?>>
            <?php
            echo Anchor(T('Public'), '/activity', 'TabLink');
            ?>
         </li>
         <?php
         if ($ModPermission): 
         ?>
         <li <?php if ($Sender->Data('Filter') == 'mods') echo 'class="Active"'; ?>>
            <?php
            echo Anchor(T('Moderator'), '/activity/mods', 'TabLink');
            ?>
         </li>
         <?php
         endif;
         
         if ($AdminPermission):
         ?>
         <li <?php if ($Sender->Data('Filter') == 'admins') echo 'class="Active"'; ?>>
            <?php
            echo Anchor(T('Admin'), '/activity/admins', 'TabLink');
            ?>
         </li>
         <?php
         endif;
         ?>
      </ul>
   </div>
<?php
}