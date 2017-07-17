<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php
        if ($this->Route !== false) {
            echo t('Edit Route');
        } else {
            echo t('Add Route');
        }
        ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Route Expression', 'Route');
                $Attributes = ['class' => 'InputBox WideInput'];
                if ($this->Route['Reserved']) {
                    //$Attributes['value'] = $this->Route;
                    $Attributes['disabled'] = 'disabled';
                } ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Route', $Attributes); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Target', 'Target'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Target', ['class' => 'InputBox WideInput']); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Type', 'Route Type'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('Type', Gdn::router()->getRouteTypes()); ?>
            </div>
        </li>
    </ul>
<?php echo $this->Form->close('Save'); ?>
