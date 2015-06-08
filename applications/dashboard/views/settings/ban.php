<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <?php $this->fireEvent("BeforeAddBanForm"); ?>
        <li>
            <?php
            echo $this->Form->label('Type', 'BanType');
            echo $this->Form->DropDown('BanType', $this->data('_BanTypes'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Value or Pattern', 'BanValue');
            echo $this->Form->textBox('BanValue');
            ?>
            <span>Use asterisks for wildcards, e.g. &lsquo;*@hotmail.com&rsquo;</span>
        </li>
        <li>
            <?php
            echo $this->Form->label('Notes', 'Notes');
            echo $this->Form->textBox('Notes');
            ?>
            <span>Optional</span>
        </li>
        <?php $this->fireEvent("AfterAddBanForm"); ?>
    </ul>
<?php echo $this->Form->close('Save'); ?>
