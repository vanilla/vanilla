<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

$CountAllowed = GetValue('CountAllowed', $this->Data, 0);
$CountNotAllowed = GetValue('CountNotAllowed', $this->Data, 0);
$CountCheckedDiscussions = GetValue('CountCheckedDiscussions', $this->Data, 0);

if ($CountNotAllowed > 0) {
   echo Wrap(sprintf(
      'You do not have permission to delete %1$s of the selected discussions.',
      $CountNotAllowed
      ), 'p');

   echo Wrap(sprintf(
      'You are about to delete %1$s of the %2$s selected discussions.',
      $CountAllowed,
      $CountCheckedDiscussions
      ), 'p');
} else {
   echo Wrap(sprintf(
      'You are about to delete %s.',
      Plural($CountAllowed, '%s discussion', '%s discussions')
      ), 'p');
}

echo '<p><strong>'.T('Are you sure you wish to continue?').'</strong></p>';
echo $this->Form->Close('Confirm Delete');
