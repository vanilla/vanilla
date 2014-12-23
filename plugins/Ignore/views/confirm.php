<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();

switch ($this->Data('Mode')) {
   case 'set':
      echo '<div class="P">'.sprintf(T('Are you sure you want to ignore <b>%s</b>?'), $this->Data('User.Name')).'</div>';
      
      if ($this->Data('Conversations')) {
         $Conversations = (array)$this->Data('Conversations');
         $NumConversationsAffected = count($Conversations);
         echo '<div class="Warning">';
         echo sprintf(T('Ignoring this person will remove you from <b>%s %s</b> with them.'), $NumConversationsAffected, Plural($NumConversationsAffected, 'conversation', 'conversations'));
         echo '</div>';
      }
      
      break;
   
   case 'unset':
      echo '<div class="P">'.sprintf(T('Are you sure you want to unignore <b>%s</b>?'), $this->Data('User.Name')).'</div>';
      break;
}

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->Button('OK', array('class' => 'Button Primary'));
echo $this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
echo '<div>';
echo $this->Form->Close();
?>
