<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php printf(T('Delete User: %s'), UserAnchor($this->User)); ?></h1>
<?php
echo $this->Form->Open(array('class' => 'User'));
echo $this->Form->Errors();
?>
<div class="Messages Errors" style="margin-bottom: 20px;">
   <ul>
      <li><?php printf(T("By clicking the button below, you will be deleting the user account for %s forever."), Wrap(htmlspecialchars($this->User->Name), 'strong')); ?></li>
      <li><?php
      if ($this->Method == 'keep')
         echo T("The user content will remain untouched.");
      else if ($this->Method == 'wipe')
         echo T("All of the user content will be replaced with a message stating the user has been deleted.");
      else
         echo T("The user content will be completely deleted.");
      ?></li>
      <li><strong><?php echo T('This action cannot be undone.'); ?></strong></li>
   </ul>
</div>
<?php
echo $this->Form->Close('Delete User Forever');