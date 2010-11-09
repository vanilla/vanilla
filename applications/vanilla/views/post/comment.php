<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$NewOrDraft = !isset($this->Comment) || property_exists($this->Comment, 'DraftID') ? TRUE : FALSE;
$Editing = isset($this->Comment);
?>
<div class="MessageForm CommentForm">
   <?php if (!$Editing) { ?>
   <div class="Tabs CommentTabs">
      <ul>
         <li class="Active"><?php echo Anchor(T('Write Comment'), '#', 'WriteButton'); ?></li>
         <?php
         if (!$Editing)
            echo '<li>'.Anchor(T('Preview'), '#', 'PreviewButton')."</li>\n";
         
         if ($NewOrDraft)
            echo '<li>'.Anchor(T('Save Draft'), '#', 'DraftButton')."</li>\n";
   
         $this->FireEvent('AfterCommentTabs');
         ?>
      </ul>
   </div>
   <?php
   } else {
      $this->Form->SetFormValue('Body', $this->Comment->Body);
   }
   echo $this->Form->Open();
   echo $this->Form->Errors();
   
   $CommentOptions = array('MultiLine' => TRUE);
   /*
    Caused non-root users to not be able to add comments. Must take categories
    into account. Look at CheckPermission for more information.
   if (!$Session->CheckPermission('Vanilla.Comment.Add')) {
      $CommentOptions['Disabled'] = 'disabled';
      $CommentOptions['Value'] = T('You do not have permission to write new comments.');
   }
   */
   
   echo $this->Form->TextBox('Body', $CommentOptions);
   echo "<div class=\"Buttons\">\n";
   $this->FireEvent('BeforeFormButtons');
   $CancelText = 'Back to Discussions';
   $CancelClass = 'Back';
   if (!$NewOrDraft) 
      $CancelText = $CancelClass = 'Cancel';

   echo Anchor(T($CancelText), 'discussions', $CancelClass);
   
   $ButtonOptions = array('class' => 'Button CommentButton');
   /*
    Caused non-root users to not be able to add comments. Must take categories
    into account. Look at CheckPermission for more information.
   if (!Gdn::Session()->CheckPermission('Vanilla.Comment.Add'))
      $ButtonOptions['Disabled'] = 'disabled';
   */

   echo $this->Form->Button($Editing ? 'Save Comment' : 'Post Comment', $ButtonOptions);
   $this->FireEvent('AfterFormButtons');
   echo "</div>\n";
   echo $this->Form->Close();
   ?>
</div>