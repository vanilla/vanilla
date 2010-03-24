<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$NewOrDraft = !isset($this->Comment) || property_exists($this->Comment, 'DraftID') ? TRUE : FALSE;
$Editing = isset($this->Comment);
?>
<div id="CommentForm">
   <h2><?php echo $Editing ? GDN::Translate('Edit Comment') : GDN::Translate('Add Comment'); ?></h2>
   <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
      echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
      echo $this->Form->Button('Post Comment');
      if ($NewOrDraft)
         echo $this->Form->Button('Save Draft');
      
      echo $this->Form->Button('Preview');
      $this->FireEvent('AfterFormButtons');
      echo Anchor(Gdn::Translate('Cancel'), '/vanilla/discussion/'.$this->DiscussionID, 'Cancel');
      echo $this->Form->Close();
   ?>
</div>