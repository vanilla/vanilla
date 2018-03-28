<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box AddPeople">
    <?php
    echo panelHeading(t('Add People to this Conversation'));
    echo $this->Form->open(['id' => 'Form_AddPeople']);
    echo wrap($this->Form->textBox('AddPeople', ['MultiLine' => true, 'class' => 'MultiComplete']), 'div', ['class' => 'TextBoxWrapper']);
    echo $this->Form->close('Add', '', ['class' => 'Button Action']);
    ?>
</div>
