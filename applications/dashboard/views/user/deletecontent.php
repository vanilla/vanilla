<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>
<div class="Wrap">
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>

    <div class="Warning">
        <?php
        echo formatString(t("You are about to delete all of a user's content.", "You are about to delete all of the content for {User.UserID,user}."), $this->Data);
        ?>
    </div>
    <?php

    echo '<div class="Buttons Buttons-Confirm">';
    echo $this->Form->button('Yes');
    echo $this->Form->button('No', array('type' => 'button', 'class' => 'Button Close'));
    echo '</div>';

    echo $this->Form->close();
    ?>
</div>
