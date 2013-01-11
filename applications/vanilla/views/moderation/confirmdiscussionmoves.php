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
      'You do not have permission to move %1$s of the selected discussions.',
      $CountNotAllowed
      ), 'p');

   echo Wrap(sprintf(
      'You are about to move %1$s of the %2$s of the selected discussions.',
      $CountAllowed,
      $CountCheckedDiscussions
      ), 'p');
} else {
echo Wrap(sprintf(
   'You are about to move %s.',
   Plural($CountCheckedDiscussions, '%s discussion', '%s discussions')
   ), 'p');
}
?>
<ul>
   <li>
      <?php
         echo '<p><div class="Category">';
         echo $this->Form->Label('Category', 'CategoryID'), ' ';
         echo $this->Form->CategoryDropDown();
         echo '</div></p>';
      ?>
   </li>
</ul>
<?php
echo $this->Form->Close('Move');
