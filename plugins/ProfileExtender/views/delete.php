<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();

echo wrap(FormatString(t("ConfirmDeleteProfileField",
    "You are about to delete the profile field &ldquo;{Field.Label}&rdquo; from all users."), $this->Data), 'p');

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->button('Delete Field');
echo $this->Form->button('Cancel', array('type' => 'button', 'class' => 'Button Close Cancel'));
echo '</div>';

echo $this->Form->close();
?>
