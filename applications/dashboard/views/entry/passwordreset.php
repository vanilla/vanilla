<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t("Reset my password") ?></h1>
<div class="Box">
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>

    <?php if (!$this->data('Fatal')) { ?>
        <ul>
            <li>
                <?php
                echo '<div class="Info">', sprintf(t('Resetting the password for %s.'), htmlspecialchars($this->data('User.Name'))), '</div>';
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('New Password', 'Password');
                echo $this->Form->Input('Password', 'password');
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Confirm Password', 'Confirm');
                echo $this->Form->Input('Confirm', 'password');
                ?>
            </li>
            <li class="Buttons">
                <?php
                echo $this->Form->button('Save your password', array('class' => 'Button Primary'));
                ?>
            </li>
        </ul>
    <?php
    } else {
        ?>
        <div class="P Center">
            <?php
            echo anchor(t('Request another password reset.'), '/entry/passwordrequest');
            ?>
        </div>
    <?php
    }
    ?>
    <?php echo $this->Form->close(); ?>
</div>
