<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();

echo '<div class="P">'.T('Where do you want to announce this discussion?').'</div>';

echo '<div class="P">', $this->Form->Radio('Announce', sprintf('In <b>%s.</b>', $this->Data('Category.Name')), array('Value' => '2')), '</div>';
echo '<div class="P">', $this->Form->Radio('Announce', sprintf('In <b>%s</b> and recent discussions.', $this->Data('Category.Name')), array('Value' => '1')), '</div>';
echo '<div class="P">', $this->Form->Radio('Announce', "Don't announce.", array('Value' => '0')), '</div>';

echo $this->Form->Button('OK');
echo $this->Form->Close();
?>
