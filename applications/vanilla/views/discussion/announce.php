<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();

echo '<div class="P">'.t('Where do you want to announce this discussion?').'</div>';

echo '<div class="P">', $this->Form->Radio('Announce', '@'.sprintf(t('In <b>%s.</b>'), $this->data('Category.Name')), array('Value' => '2')), '</div>';
echo '<div class="P">', $this->Form->Radio('Announce', '@'.sprintf(t('In <b>%s</b> and recent discussions.'), $this->data('Category.Name')), array('Value' => '1')), '</div>';
echo '<div class="P">', $this->Form->Radio('Announce', '@'.t("Don't announce."), array('Value' => '0')), '</div>';

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->button('OK');
echo $this->Form->button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
echo '<div>';
echo $this->Form->close();
?>
