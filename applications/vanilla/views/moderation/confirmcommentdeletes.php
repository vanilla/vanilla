<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

$CountCheckedComments = GetValue('CountCheckedComments', $this->Data, 0);
echo Wrap(sprintf(
   'You are about to delete %s.',
   Plural($CountCheckedComments, '%s comment', '%s comments')
   ), 'p');

echo '<p><strong>'.T('Are you sure you wish to continue?').'</strong></p>';
echo $this->Form->Close('Confirm Delete');
