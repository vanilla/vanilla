<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box AddPeople">
    <?php
    echo panelHeading(t('Add People to this Conversation'));
    echo $this->Form->open(array('id' => 'Form_AddPeople'));
    echo wrap($this->Form->textBox('AddPeople', array('MultiLine' => true, 'class' => 'MultiComplete')), 'div', array('class' => 'TextBoxWrapper'));
    echo $this->Form->close('Add', '', array('class' => 'Button Action'));
    ?>
</div>
