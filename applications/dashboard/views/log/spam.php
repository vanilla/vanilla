<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Info"><?php echo T('Content flagged as spam is stored here for moderator review.'); ?></div>
<?php
echo '<noscript><div class="Errors"><ul><li>', T('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->Open();
?>
<div class="Info">
   <?php
   echo Anchor(T('Spam'), '#', array('class' => 'SpamButton SmallButton'));
   echo Anchor(T('Not Spam'), '#', array('class' => 'NotSpamButton SmallButton'));
   ?>
</div>
<?php

echo '<div id="LogTable">';
include dirname(__FILE__).'/table.php';
echo '</div id="LogTable">';
?>
<div class="Info">
   <?php
   echo Anchor(T('Spam'), '#', array('class' => 'SpamButton SmallButton'));
   echo Anchor(T('Not Spam'), '#', array('class' => 'NotSpamButton SmallButton'));
   ?>
</div>
<?php

$this->AddDefinition('ExpandText', T('(more)'));
$this->AddDefinition('CollapseText', T('(less)'));
echo $this->Form->Close();