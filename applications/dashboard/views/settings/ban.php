<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <?php $this->fireEvent("BeforeAddBanForm"); ?>
        <li class="form-group">
            <div class="label-wrap">
            <?php echo $this->Form->label('Type', 'BanType'); ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->dropDown('BanType', $this->data('_BanTypes')); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            <?php echo $this->Form->label('Value or Pattern', 'BanValue'); ?>
            <div class="info"><?php echo t('Use asterisks for wildcards', 'Use asterisks for wildcards, e.g. &lsquo;*@hotmail.com&rsquo;'); ?></div>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->textBox('BanValue'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            <?php echo $this->Form->label('Notes', 'Notes'); ?>
            <div class="info"><?php echo t('Optional'); ?></div>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->textBox('Notes'); ?>
            </div>
        </li>
        <?php $this->fireEvent("AfterAddBanForm"); ?>
    </ul>
<?php echo $this->Form->close('Save'); ?>
