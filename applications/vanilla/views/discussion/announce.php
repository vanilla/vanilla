<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();

echo '<div class="P">'.T('Where do you want to announce this discussion?').'</div>';

echo '<div class="P">', $this->Form->Radio('Announce', '@'.sprintf(T('In <b>%s.</b>'), $this->Data('Category.Name')), array('Value' => '2')), '</div>';
echo '<div class="P">', $this->Form->Radio('Announce', '@'.sprintf(T('In <b>%s</b> and recent discussions.'), $this->Data('Category.Name')), array('Value' => '1')), '</div>';
echo '<div class="P">', $this->Form->Radio('Announce', '@'.T("Don't announce."), array('Value' => '0')), '</div>';

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->Button('OK');
echo $this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
echo '<div>';
echo $this->Form->Close();
?>
