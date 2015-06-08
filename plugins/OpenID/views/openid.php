<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t($this->Data['Title'].' Settings'); ?></h1>
<div class="Info">
    <?php echo t($this->Data['Description']); ?>
</div>
<div class="FilterMenu">
    <?php
    $FormAction = $this->Plugin->AutoTogglePath();
    echo $this->Form->open(array(
        'action' => url($FormAction),
        'jsaction' => $FormAction
    ));
    echo $this->Form->errors();

    $PluginName = $this->Plugin->GetPluginKey('Name');
    $ButtonName = t($this->Plugin->IsEnabled() ? "Disable {$PluginName}" : "Enable {$PluginName}");

    echo $this->Form->close($ButtonName, '', array(
        'class' => 'SliceSubmit SliceForm SmallButton'
    ));
    ?>
</div>
