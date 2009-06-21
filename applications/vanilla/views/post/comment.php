<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$NewOrDraft = !isset($this->Comment) || $this->Comment->Draft == '1' ? TRUE : FALSE;
$Editing = isset($this->Comment);
?>
<div id="CommentForm">
   <?php
      echo $this->Form->Open();
      echo $this->Form->Label(Gdn::Translate($Editing ? 'Edit Comment' : 'Add Comment'), 'Body', array('class' => 'Heading'));
      echo $this->Form->Errors();
      echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
      echo $this->Form->Button(Gdn::Translate('Post Comment'));
      if ($NewOrDraft)
         echo $this->Form->Button(Gdn::Translate('Save Draft'));
      
      echo $this->Form->Button(Gdn::Translate('Preview'));
      echo Anchor(Gdn::Translate('Cancel'), '/vanilla/discussion/'.$this->DiscussionID, 'Cancel');
      echo $this->Form->Close();
   ?>
</div>