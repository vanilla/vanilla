<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Info"><?php echo T('To prevent abuse, some tools automatically hide content and list it here until it is manually approved by a moderator.'); ?></div>

<?php
echo '<noscript><div class="Errors"><ul><li>', T('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->Open();
?>
<div class="FilterMenu"><?php
   if (C('Vanilla.Categories.Use')) {
      echo Wrap(sprintf(
         T('Vanilla.Moderation.FilterBy', 'Show moderation queue for %1$s'),
            $this->Form->CategoryDropDown('CategoryID', array(
               'Value' => GetValue('ModerationCategoryID', $this->Data),
               'IncludeNull' => 'Everything'))
      ).' '.Anchor(T('Filter'), '#', array('class' => 'FilterButton SmallButton')), 'div');
   }
?></div>
<div class="Info">
   <?php
   echo Anchor(T('Approve'), '#', array('class' => 'RestoreButton SmallButton'));
   echo Anchor(T('Delete Forever'), '#', array('class' => 'DeleteButton SmallButton'));
   ?>
</div>
<?php
echo '<div id="LogTable">';
include dirname(__FILE__).'/table.php';
echo '</div id="LogTable">';
?>
<div class="Info">
   <?php
   echo Anchor(T('Approve'), '#', array('class' => 'RestoreButton SmallButton'));
   echo Anchor(T('Delete Forever'), '#', array('class' => 'DeleteButton SmallButton'));
   ?>
</div>
<?php

$this->AddDefinition('ExpandText', T('(more)'));
$this->AddDefinition('CollapseText', T('(less)'));
echo $this->Form->Close();