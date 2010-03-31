<?php if (!defined('APPLICATION')) exit();

function WriteActivity($Activity, &$Sender, &$Session, $Comment) {
   ?>
<li id="Activity_<?php echo $Activity->ActivityID; ?>" class="Activity<?php
   echo ' ' . $Activity->ActivityType;
   if ($Activity->ActivityPhoto != '' && $Activity->ShowIcon == '1')
      echo ' HasPhoto';
?>"><?php
   if (
      $Session->IsValid()
      && ($Session->UserID == $Activity->InsertUserID
         || $Session->CheckPermission('Garden.Activity.Delete'))
      )
      echo Anchor(T('Delete'), 'garden/activity/delete/'.$Activity->ActivityID.'/'.$Session->TransientKey().'?Return='.urlencode(Gdn_Url::Request()), 'Delete');

   // If this was a status update or a wall comment, don't bother with activity strings
   $ActivityType = explode(' ', $Activity->ActivityType); // Make sure you strip out any extra css classes munged in here
   $ActivityType = $ActivityType[0];
   $Author = UserBuilder($Activity, 'Activity');
   if (in_array($ActivityType, array('WallComment', 'AboutUpdate'))) {
      echo UserPhoto($Author, 'Photo');
      echo '<div>';
         echo UserAnchor($Author, 'Name');
         if ($Activity->ActivityType == 'WallComment' && $Activity->RegardingUserID > 0 && (!property_exists($Sender, 'ProfileUserID') || $Sender->ProfileUserID != $Activity->RegardingUserID)) {
            $Author = UserBuilder($Activity, 'Regarding');
            echo '<span>→</span>'.UserAnchor($Author, 'Name');
         }
         echo Format::Display($Activity->Story);
         echo '<div class="Meta">';
            echo Format::Date($Activity->DateInserted);
            echo $Activity->AllowComments == '1' && $Session->IsValid() ? '<span>&bull;</span>'.Anchor(T('Comment'), '#CommentForm_'.$Activity->ActivityID, 'CommentOption') : '';
         echo '</div>';
      echo '</div>';
   } else {
      if ($Activity->ShowIcon == '1')
         echo UserPhoto($Author, 'Photo');

      echo '<div>';
         echo Format::ActivityHeadline($Activity, $Sender->ProfileUserID);
         echo '<div class="Meta">';
            echo Format::Date($Activity->DateInserted);
            echo $Activity->AllowComments == '1' && $Session->IsValid() ? '<span>&bull;</span>'.Anchor(T('Comment'), '#CommentForm_'.$Activity->ActivityID, 'CommentOption') : '';
            if ($Activity->Story != '') {
               echo '<div class="Story">';
                  echo $Activity->Story; // story should be cleaned before being saved.
               echo '</div>';
            }
         echo '</div>';
      echo '</div>';
   }
   if ($Activity->AllowComments == '1') {
      // If there are comments, show them
      $FoundComments = FALSE;
      if (property_exists($Sender, 'CommentData') && is_object($Sender->CommentData)) {
         foreach ($Sender->CommentData->Result() as $Comment) {
            if (is_object($Comment) && $Comment->CommentActivityID == $Activity->ActivityID) {
               if ($FoundComments == FALSE)
                  echo '<ul class="Comments">';
                  
               $FoundComments = TRUE;
               WriteActivityComment($Comment, $Sender, $Session);
            }
         }
      }
      if ($FoundComments == FALSE)
         echo '<ul class="Comments Hidden">';

      if ($Session->IsValid()) {
         ?>
         <li class="CommentForm">
         <?php
            echo Anchor(T('Write a comment'), '/garden/activity/comment/'.$Activity->ActivityID, 'CommentLink');
            $CommentForm = Gdn::Factory('Form');
            $CommentForm->SetModel($Sender->ActivityModel);
            $CommentForm->AddHidden('ActivityID', $Activity->ActivityID);
            $CommentForm->AddHidden('Return', Gdn_Url::Request());
            echo $CommentForm->Open(array('action' => Url('/garden/activity/comment'), 'class' => 'Hidden'));
            echo $CommentForm->TextBox('Body', array('MultiLine' => TRUE, 'value' => ''));
            echo $CommentForm->Close('Comment');
         ?></li>
      <?php } ?>
      </ul>
   <?php } ?>
</li>
<?php
}

function WriteActivityComment($Comment, &$Sender, &$Session) {
   $Author = UserBuilder($Comment, 'Activity');
?>
<li id="Activity_<?php echo $Comment->ActivityID; ?>" class="<?php
   echo $Comment->ActivityType;
   if ($Comment->ActivityPhoto != '')
      echo ' HasPhoto';
?>"><?php
   echo UserPhoto($Author, 'Photo');
   echo '<div>';
      echo UserAnchor($Author, 'Name');
      echo Format::Display($Comment->Story);
      echo '<div class="Meta">';
         echo Format::Date($Comment->DateInserted);
         echo $Session->UserID == $Comment->InsertUserID || $Session->CheckPermission('Garden.Activity.Delete') ? '<span>&bull;</span>'.Anchor(T('Delete'), 'garden/activity/delete/'.$Comment->ActivityID.'/'.$Session->TransientKey().'?Return='.urlencode(Gdn_Url::Request())) : '';
      echo '</div>';
   echo '</div>';
   ?>
</li>
<?php
}