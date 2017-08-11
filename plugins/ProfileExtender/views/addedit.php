<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<div class="js-profile-extender-form">
    <div class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Type', 'FormType'); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->dropdown('FormType', $this->data('FormTypes')); ?>
        </div>
    </div>
    <div class="js-label<?php if ($this->Form->getValue('FormType') == 'DateOfBirth') echo ' Hidden'; ?>">
        <div class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Label', 'Label'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Label'); ?>
            </div>
        </div>
    </div>
    <div class="js-options<?php if ($this->Form->getValue('FormType') != 'Dropdown') echo ' Hidden'; ?>">
        <div class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Options', 'Options');
                echo wrap(t('One option per line'), 'div', ['class' => 'info']); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Options', ['MultiLine' => TRUE]); ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="input-wrap no-label">
            <?php echo $this->Form->checkBox('Required', 'Required for all users'); ?>
        </div>
    </div>
    <div class="form-group">
        <div class="input-wrap no-label">
            <?php echo $this->Form->checkBox('OnRegister', 'Show on registration'); ?>
        </div>
    </div>
    <div class="js-show-on-profiles">
        <div class="form-group">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkBox('OnProfile', 'Show on profiles'); ?>
            </div>
        </div>
    </div>
    <!--<div>
         <?php echo $this->Form->checkBox('OnDiscussion', 'Show on discussions'); ?>
      </div>-->
</div>
<?php echo $this->Form->close('Save'); ?>
