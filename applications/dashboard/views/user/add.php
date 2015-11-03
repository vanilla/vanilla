<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php
        if (is_object($this->User))
            echo t('Edit User');
        else
            echo t('Add User');
        ?></h1>
<?php
echo $this->Form->open(array('class' => 'User'));
echo $this->Form->errors();
?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Username', 'Name');
            echo $this->Form->textBox('Name');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Password', 'Password');
            echo $this->Form->input('Password', 'password', array('class' => 'InputBox js-password '));
            echo ' '.$this->Form->checkbox('NoPassword', 'No password', array('class' => 'Inline CheckBoxLabel js-nopassword'));
            ?>
            <div class="InputButtons js-password-related">
                <?php
                echo anchor(t('Generate Password'), '#', 'GeneratePassword Button SmallButton');
                echo anchor(t('Reveal Password'), '#', 'RevealPassword Button SmallButton');
                ?>
            </div>
        </li>
        <li>
            <?php
            echo $this->Form->label('Email', 'Email');
            echo $this->Form->textBox('Email');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->checkBox('ShowEmail', t('Email visible to other users'));
            ?>
        </li>
        <?php
        $this->fireEvent('CustomUserFields')
        ?>
        <li>
            <strong><?php echo t('Check all roles that apply to this user:'); ?></strong>
            <?php echo $this->Form->checkBoxList("RoleID", array_flip($this->RoleData), array_flip($this->UserRoleData)); ?>
        </li>
    </ul>
<?php echo $this->Form->close('Save');
