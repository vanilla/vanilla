<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li class="form-group">
            <?php
            echo '<div class="label-wrap">'.$this->Form->label('Tag Name', 'FullName').'</div>';
            echo '<div class="input-wrap">'.$this->Form->textBox('FullName').'</div>';
            ?>
        </li>
        <li class="form-group">
            <?php
            echo '<div class="label-wrap">'.$this->Form->label('Url Slug', 'Name').'</div>';
            echo '<div class="input-wrap">'.$this->Form->textBox('Name').'</div>';
            ?>
        </li>
        <?php if ($this->data('MergeTagVisible')): ?>
            <li>
                <?php
                echo $this->Form->checkBox('MergeTag', 'Merge this tag with the existing one');
                ?>
            </li>
        <?php endif; ?>
    </ul>
<?php echo $this->Form->close('Save');
