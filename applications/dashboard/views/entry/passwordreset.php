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
                echo wrap(sprintf(t('Your password must be at least %d characters long.'), c('Garden.Password.MinLength')).' '.t('For a stronger password, increase its length or combine upper and lowercase letters, digits, and symbols.'), 'div', ['class' => 'Gloss']);
                echo $this->Form->input('Password', 'password', ['Wrap' => true, 'Strength' => true]);
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Confirm Password', 'PasswordMatch');
                echo $this->Form->input('PasswordMatch', 'password',  ['Wrap' => true]);
                echo '<span id="PasswordsDontMatch" class="Incorrect" style="display: none;">'.t("Passwords don't match").'</span>';
                ?>
            </li>
            <li class="Buttons">
                <?php
                echo $this->Form->button('Save your password', ['class' => 'Button Primary']);
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
