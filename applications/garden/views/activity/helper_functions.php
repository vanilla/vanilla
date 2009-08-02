<?php if (!defined('APPLICATION')) exit();

function WriteActivity($Activity, &$Sender, &$Session, $Comment) {
   ?>
<li id="Activity_<?php echo $Activity->ActivityID; ?>" class="Activity<?php echo ' ' . $Activity->ActivityType; ?>"><?php
   if (
      $Session->IsValid()
      && ($Session->UserID == $Activity->InsertUserID
         || $Session->CheckPermission('Garden.Activity.Delete'))
      )
      echo Anchor('Delete', 'garden/activity/delete/'.$Activity->ActivityID.'/'.$Session->TransientKey().'?Return='.urlencode(Gdn_Url::Request()), 'Delete');

   if ($Activity->ActivityPhoto != '' && $Activity->ShowIcon == '1') {
      if ($Activity->InsertUserID == $Session->UserID) {
         echo '<a href="'.Url('/garden/profile/'.urlencode($Activity->ActivityName)).'">'
            .$Sender->Html->Image('uploads/n'.$Activity->ActivityPhoto)
         .'</a>';
      } else {
         echo $Sender->Html->Image('uploads/n'.$Activity->ActivityPhoto);
      }
   }
   ?><h3><?php
      echo Format::ActivityHeadline($Activity, $Sender->ProfileUserID);
   ?><em><?php
      echo Format::Date($Activity->DateInserted);
   ?></em><?php
      echo $Activity->AllowComments == '1' && $Session->IsValid() ? ' '.Anchor('Comment', '#CommentForm_'.$Activity->ActivityID, 'CommentOption') : '';
   ?></h3><?php
   if ($Activity->Story != '') {
   ?><blockquote><?php
      echo $Activity->Story; // story should be cleaned before being saved.
   ?></blockquote>
   <?php
   }
      if ($Activity->AllowComments == '1') {
         // If there are comments, show them
         if (is_object($Comment) && $Comment->CommentActivityID == $Activity->ActivityID) {
            
            ?><ul class="Comments"><?php
            while (is_object($Comment) && $Comment->CommentActivityID == $Activity->ActivityID) {
               if (is_object($Comment))
                  WriteActivityComment($Comment, $Sender, $Session);
                  
               $Comment = $Sender->CommentData->NextRow();
            }
         } else {
            ?><ul class="Comments Hidden"><?php
         }
         if ($Session->IsValid()) {
            ?>
            <li class="CommentForm">
            <?php
               echo Anchor('Write a comment', '/garden/activity/comment/'.$Activity->ActivityID, 'CommentLink');
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
?>
<li id="Activity_<?php echo $Comment->ActivityID; ?>" class="<?php echo $Comment->ActivityType; ?>"><?php
   if ($Comment->ActivityPhoto != '') {
      if ($Comment->InsertUserID == $Session->UserID) {
         echo '<a href="'.Url('/garden/profile/'.urlencode($Comment->ActivityName)).'">'
            .$Sender->Html->Image('uploads/n'.$Comment->ActivityPhoto)
         .'</a>';
      } else {
         echo $Sender->Html->Image('uploads/n'.$Comment->ActivityPhoto);
      }
   }
   ?><h3><?php
      echo $Session->UserID == $Comment->InsertUserID || $Session->CheckPermission('Garden.Activity.Delete') ? Anchor('Delete', 'garden/activity/delete/'.$Comment->ActivityID.'/'.$Session->TransientKey().'?Return='.urlencode(Gdn_Url::Request()), 'Delete') : '';
   ?><strong><?php
      echo Format::ActivityHeadline($Comment, $Sender->ProfileUserID);
   ?><em><?php
      echo Format::Date($Comment->DateInserted);
   ?></em></strong></h3>
   <blockquote><?php
      echo Format::Display($Comment->Story);
   ?></blockquote>
</li>
<?php
}