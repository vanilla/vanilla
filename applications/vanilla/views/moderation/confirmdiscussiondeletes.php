<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();

$CountAllowed = val('CountAllowed', $this->Data, 0);
$CountNotAllowed = val('CountNotAllowed', $this->Data, 0);
$CountCheckedDiscussions = val('CountCheckedDiscussions', $this->Data, 0);

if ($CountNotAllowed > 0) {
    echo wrap(sprintf(
        t('NoPermissionToDeleteDiscussions', 'You do not have permission to delete %1$s of the selected discussions.'),
        $CountNotAllowed
    ), 'p');

    echo wrap(sprintf(
        t('AboutToDeleteSelectedDiscussions', 'You are about to delete %1$s of the %2$s selected discussions.'),
        $CountAllowed,
        $CountCheckedDiscussions
    ), 'p');
} else {
    echo wrap(sprintf(
        t('AboutToDelete', 'You are about to delete %s.'),
        plural($CountAllowed, '%s discussion', '%s discussions')
    ), 'p');
}

echo '<p><strong>'.t('Are you sure you wish to continue?').'</strong></p>';

echo '<div class="Buttons Buttons-Confirm">',
$this->Form->button('OK', ['class' => 'Button Primary']),
$this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']),
'</div>';

echo $this->Form->close();
