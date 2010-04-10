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
   echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
   echo Anchor(T('Cancel'), '/vanilla/discussion/'.$this->DiscussionID, 'Cancel');
   echo $this->Form->Button($Editing ? 'Save Comment' : 'Post Comment', array('class' => 'Button CommentButton'));
   $this->FireEvent('AfterFormButtons');
   echo $this->Form->Close();
   ?>
</div>