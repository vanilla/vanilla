<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t($this->Data['Title'].' Settings'); ?></h1>
<div class="Info">
    <?php echo t($this->Data['Description']); ?>
</div>
<div class="FilterMenu">
    <?php
    $FormAction = $this->Plugin->autoTogglePath();
    echo $this->Form->open([
        'action' => url($FormAction),
        'jsaction' => $FormAction
    ]);
    echo $this->Form->errors();

    $PluginName = $this->Plugin->getPluginKey('Name');
    $ButtonName = t($this->Plugin->isEnabled() ? "Disable {$PluginName}" : "Enable {$PluginName}");

    echo $this->Form->close($ButtonName, '', ['class' => 'SmallButton']);
    ?>
</div>
