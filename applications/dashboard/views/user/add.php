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
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Username', 'Name'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Name'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Email', 'Email'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Email'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkBox('ShowEmail', 'Email visible to other users'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Password', 'Password'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->input('Password', 'password', array('class' => 'InputBox js-password ')); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="InputButtons js-password-related input-wrap no-label">
                <?php echo anchor(t('Generate Password'), '#', 'GeneratePassword btn btn-secondary');
                echo anchor(t('Reveal Password'), '#', 'RevealPassword btn btn-secondary'); ?>
            </div>
        </li>

        <?php if (c('Garden.Profile.Locations', false)): ?>
            <li class="form-group User-Location">
                <div class="label-wrap">
                    <?php echo $this->Form->label('Location', 'Location'); ?>
                </div>
                <div class="input-wrap">
                    <?php echo $this->Form->textBox('Location'); ?>
                </div>
            </li>
        <?php endif; ?>

        <?php if (c('Garden.Profile.Titles', false)): ?>
            <li class="form-group User-Title">
                <div class="label-wrap">
                    <?php echo $this->Form->label('Title', 'Title'); ?>
                </div>
                <div class="input-wrap">
                    <?php echo $this->Form->textBox('Title'); ?>
                </div>
            </li>
        <?php endif; ?>
        <?php
        $this->fireEvent('CustomUserFields')
        ?>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo t('Check all roles that apply to this user:');  ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->checkBoxList("RoleID", array_flip($this->RoleData), array_flip($this->UserRoleData)); ?>
            </div>
        </li>
    </ul>
<?php echo $this->Form->close('Save'); ?>
