<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();

echo '<div class="P">'.t('Are you sure you want to leave this conversation?').'</div>';

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->button('OK', ['class' => 'Button Primary']);
echo $this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']);
echo '</div>';
echo $this->Form->close();