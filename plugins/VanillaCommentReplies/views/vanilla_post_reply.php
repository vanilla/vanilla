<?php if (!defined('APPLICATION')) exit(); ?>
<div id="ReplyForm">
   <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
      echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
      echo $this->Form->Button('Reply');
      echo Anchor(T('Cancel'), '/vanilla/discussion/'.$this->ReplyComment->DiscussionID, 'Cancel');
      echo $this->Form->Close();
   ?>
</div>