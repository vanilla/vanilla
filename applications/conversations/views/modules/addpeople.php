<?php if (!defined('APPLICATION')) exit();
use Vanilla\Theme\BoxThemeShim;
?>
<div class="Box AddPeople">
    <?php
    BoxThemeShim::startHeading();
    echo panelHeading(t('Add People to this Conversation'));
    BoxThemeShim::endHeading();

    $formClass = BoxThemeShim::isActive() ? 'pageBox' : '';

    echo $this->Form->open(['id' => 'Form_AddPeople', 'class' => $formClass]);

    BoxThemeShim::startBox();
    echo wrap($this->Form->textBox('AddPeople', ['MultiLine' => true, 'class' => 'MultiComplete']), 'div', ['class' => 'TextBoxWrapper']);
    echo $this->Form->close('Add', '', ['class' => 'Button Action']);
    BoxThemeShim::endBox();
    ?>
</div>
