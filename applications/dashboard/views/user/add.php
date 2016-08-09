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
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->label('Username', 'Name'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Name'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->label('Password', 'Password'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->input('Password', 'password', array('class' => 'InputBox js-password ')); ?>
            </div>
        </li>
        <li>
            <?php
            echo $this->Form->label('Email', 'Email');
            </div>
        </li>
        <li class="form-group row">
            <div class="InputButtons js-password-related input-wrap no-label">
                <?php echo anchor(t('Generate Password'), '#', 'GeneratePassword btn btn-secondary');
                echo anchor(t('Reveal Password'), '#', 'RevealPassword btn btn-secondary'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->label('Email', 'Email'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Email'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkBox('ShowEmail', t('Email visible to other users')); ?>
            </div>
        </li>

        <?php if (c('Garden.Profile.Locations', false)): ?>
            <li class="User-Location">
                <?php
                echo $this->Form->label('Location', 'Location');
                echo $this->Form->textBox('Location');
                ?>
            </li>
        <?php endif; ?>

        <?php if (c('Garden.Profile.Titles', false)): ?>
            <li class="User-Title">
                <?php
                echo $this->Form->label('Title', 'Title');
                echo $this->Form->textBox('Title');
                ?>
            </li>
        <?php endif; ?>
        <?php
        $this->fireEvent('CustomUserFields')
        ?>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo t('Check all roles that apply to this user:');  ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->checkBoxList("RoleID", array_flip($this->RoleData), array_flip($this->UserRoleData)); ?>
            </div>
        </li>
    </ul>
<div class="form-footer js-modal-footer">
    <?php echo $this->Form->close('Save'); ?>
</div>
