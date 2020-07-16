<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();

switch ($this->data('Mode')) {
   case 'set':
      echo '<div class="P">'.sprintf(t('Are you sure you want to ignore <b>%s</b>?'), htmlspecialchars($this->data('User.Name'))).'</div>';

      if ($this->data('Conversations')) {
         $Conversations = (array)$this->data('Conversations');
         $NumConversationsAffected = count($Conversations);
         echo '<div class="Warning">';
         echo sprintf(t('Ignoring this person will remove you from <b>%s %s</b> with them.'), $NumConversationsAffected, plural($NumConversationsAffected, 'conversation', 'conversations'));
         echo '</div>';
      }

      break;

   case 'unset':
      echo '<div class="P">'.sprintf(t('Are you sure you want to unignore %s?'), htmlspecialchars($this->data('User.Name'))).'</div>';
      break;
}

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->button('OK', ['class' => 'Button Primary']);
echo $this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']);
echo '<div>';
echo $this->Form->close();
?>
