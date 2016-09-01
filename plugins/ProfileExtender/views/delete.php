<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();

echo wrap(FormatString(t("ConfirmDeleteProfileField",
    "You are about to delete the profile field &ldquo;{Field.Label}&rdquo; from all users."), $this->Data), 'div');

echo '<div class="js-modal-footer form-footer">';
echo $this->Form->button('Cancel', array('type' => 'button', 'class' => 'btn btn-link js-modal-close'));
echo $this->Form->button('Delete Field');
echo '</div>';

echo $this->Form->close();
?>
