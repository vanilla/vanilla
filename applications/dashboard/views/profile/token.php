<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
    <?php BoxThemeShim::startHeading(); ?>
    <h1 class="H"><?php echo $this->data('Title'); ?></h1>
    <?php BoxThemeShim::endHeading(); ?>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>
    <ul class="pageBox">
        <li class="pageBox">
            <?php
            echo $this->Form->label('Token Name', 'Name');
            echo $this->Form->textBox('Name');
            ?>
        </li>
    </ul>
    <?php echo $this->Form->close('Generate', '', ['class' => 'Button Primary']); ?>
</div>
