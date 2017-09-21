<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
    <h1 class="H"><?php echo $this->data('Title'); ?></h1>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Token Name', 'Name');
            echo $this->Form->textBox('Name');
            ?>
        </li>
    </ul>
    <?php echo $this->Form->close('Generate', '', ['class' => 'Button Primary']); ?>
</div>
