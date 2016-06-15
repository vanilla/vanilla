<?php if (!defined('APPLICATION')) exit(); ?>
<div class="header-block">
    <h1><?php echo $this->data('Title'); ?></h1>
    <?php echo anchor(t('Add Item'), '/dashboard/settings/bans/add', array('class' => 'btn btn-primary Add')); ?>
</div>
<?php
Gdn_Theme::assetBegin('Help');
echo '<h2>'.sprintf(t('About %s'), t('Banning')).'</h2>';
echo '<p>'.t('You can ban IP addresses, email domains, and words from usernames using this tool.').'</p>';
Gdn_Theme::assetEnd();
echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
echo '<div id="BansTable">';
include dirname(__FILE__).'/banstable.php';
echo '</div id="BansTable">';

echo $this->Form->close();
