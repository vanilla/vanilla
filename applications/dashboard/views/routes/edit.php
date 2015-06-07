<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php
        if ($this->Route !== FALSE)
            echo t('Edit Route');
        else
            echo t('Add Route');
        ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Route Expression', 'Route');
            $Attributes = array('class' => 'InputBox WideInput');
            if ($this->Route['Reserved']) {
                //$Attributes['value'] = $this->Route;
                $Attributes['disabled'] = 'disabled';
            }
            echo $this->Form->textBox('Route', $Attributes);
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Target', 'Target');
            echo $this->Form->textBox('Target', array('class' => 'InputBox WideInput'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Type', 'Route Type');
            echo $this->Form->DropDown('Type', Gdn::router()->GetRouteTypes());
            ?>
        </li>
    </ul>
<?php echo $this->Form->close('Save'); ?>
