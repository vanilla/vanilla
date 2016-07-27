<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->label('Type', 'FormType'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->Dropdown('FormType', $this->data('FormTypes')); ?>
            </div>
        </li>
        <li class="form-group row Label<?php if ($this->Form->getValue('FormType') == 'DateOfBirth') echo ' Hidden'; ?>">
            <div class="label-wrap">
                <?php echo $this->Form->label('Label', 'Label'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Label'); ?>
            </div>
        </li>
        <li class="form-group row Options<?php if ($this->Form->getValue('FormType') != 'Dropdown') echo ' Hidden'; ?>">
            <div class="label-wrap">
                <?php echo $this->Form->label('Options', 'Options');
                echo wrap(t('One option per line'), 'div', ['class' => 'info']); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Options', array('MultiLine' => TRUE)); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkBox('Required', 'Required for all users'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkBox('OnRegister', 'Show on registration'); ?>
            </div>
        </li>
        <li class="ShowOnProfiles form-group row">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkBox('OnProfile', 'Show on profiles'); ?>
            </div>
        </li>
        <!--<li>
         <?php echo $this->Form->checkBox('OnDiscussion', 'Show on discussions'); ?>
      </li>-->
    </ul>
<div class="form-footer js-modal-footer">
    <?php echo $this->Form->close('Save'); ?>
</div>
