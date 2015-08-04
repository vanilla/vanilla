<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
    <div
        class="Info"><?php echo t('You can ban IP addresses, email domains, and words from usernames using this tool.'); ?></div>
<?php
echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
?>
    <div class="Info">
        <?php
        echo anchor(t('Add Item'), '/dashboard/settings/bans/add', array('class' => 'SmallButton Add'));
        ?>
    </div>
<?php

echo '<div id="BansTable">';
include dirname(__FILE__).'/banstable.php';
echo '</div id="BansTable">';

echo $this->Form->close();
