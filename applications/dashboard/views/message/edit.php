<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php
        if (is_object($this->Message))
            echo t('Edit Message');
        else
            echo t('Add Message');
        ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Appearance', 'CssClass'); ?>
            </div>
            <div class="input-wrap">
                <?php
                echo $this->Form->radio('CssClass', 'Casual', array('value' => 'CasualMessage'));
                echo $this->Form->radio('CssClass', 'Information', array('value' => 'InfoMessage'));
                echo $this->Form->radio('CssClass', 'Alert', array('value' => 'AlertMessage'));
                echo $this->Form->radio('CssClass', 'Warning', array('value' => 'WarningMessage'));
                ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            <?php echo $this->Form->label('Message', 'Content'); ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->textBox('Content', array('MultiLine' => TRUE)); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Page', 'Location'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('Location', $this->data('Locations')); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Position', 'AssetTarget'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('AssetTarget', $this->AssetData); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Category', 'CategoryID'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('CategoryID', $this->data('Categories'), array('IncludeNull' => t('All Categories'))); ?>
            </div>
            <div class="input-wrap no-label padded-top">
                <?php echo $this->Form->checkBox('IncludeSubcategories', 'Include Subcategories'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkBox('AllowDismiss', 'Allow users to dismiss this message', array('value' => '1')); ?>
            </div>
        </li>
        <li class="form-group">
            <?php echo $this->Form->toggle('Enabled', 'Enable this message', array('value' => '1')); ?>
        </li>
    </ul>
<?php echo $this->Form->close('Save');
