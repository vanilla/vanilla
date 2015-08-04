<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();

$CountCheckedComments = val('CountCheckedComments', $this->Data, 0);
echo wrap(sprintf(
    t('AboutToDelete', 'You are about to delete %s.'),
    plural($CountCheckedComments, '%s comment', '%s comments')
), 'p');

echo '<p><strong>'.t('Are you sure you wish to continue?').'</strong></p>';

echo '<div class="Buttons Buttons-Confirm">',
$this->Form->button('OK', array('class' => 'Button Primary')),
$this->Form->button('Cancel', array('type' => 'button', 'class' => 'Button Close')),
'</div>';

echo $this->Form->close();
