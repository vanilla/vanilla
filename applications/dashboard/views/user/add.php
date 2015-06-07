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
            echo $this->Form->Input('Password', 'password');
            ?>
            <div class="InputButtons">
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
            echo $this->Form->CheckBox('ShowEmail', t('Email visible to other users'));
            ?>
        </li>
        <?php
        $this->fireEvent('CustomUserFields')
        ?>
        <li>
            <strong><?php echo t('Check all roles that apply to this user:'); ?></strong>
            <?php echo $this->Form->CheckBoxList("RoleID", array_flip($this->RoleData), array_flip($this->UserRoleData)); ?>
        </li>
    </ul>
<?php echo $this->Form->close('Save');
