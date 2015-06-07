<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Tag Name', 'FullName');
            echo $this->Form->textBox('FullName');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Url Slug', 'Name');
            echo $this->Form->textBox('Name');
            ?>
        </li>
        <?php if ($this->Data('MergeTagVisible')): ?>
            <li>
                <?php
                echo $this->Form->CheckBox('MergeTag', 'Merge this tag with the existing one');
                ?>
            </li>
        <?php endif; ?>
    </ul>
<?php echo $this->Form->close('Save');
