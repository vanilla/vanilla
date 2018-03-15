<?php if (!defined('APPLICATION')) exit();

echo wrap($this->data('Title'), 'h1');
echo $this->Form->open(), $this->Form->errors();
echo $this->Form->simple($this->data('_Controls'));

echo '<div class="js-modal-footer form-footer buttons">';
echo $this->Form->button('Save');
echo '</div>';

echo $this->Form->close();
