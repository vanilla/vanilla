<?php if (!defined('APPLICATION')) exit; ?>
    <style>
        .Complete {
            text-decoration: line-through;
        }

        .Error {
            color: red;
            text-decoration: line-through;
        }
    </style>

    <h1><?php echo t('Fix User Roles'); ?></h1>
<?php echo $this->Form->open(); ?>

    <div class="Info">
        <?php if ($this->data('CompletedFix')) : ?>
            <p>
                <strong><?php echo t('Operation completed successfully'); ?></strong>
            </p>
        <?php endif; ?>

        <p>
            <?php echo t('All users with an invalid or no role will be updated with the following role assignment.'); ?>
        </p>

        <?php echo $this->Form->errors(); ?>
    </div>
    <div>
        <ul>
            <li><?php
                $RoleModel = new RoleModel();
                echo $this->Form->label('Default User Role', 'DefaultUserRole');
                echo $this->Form->DropDown(
                    'DefaultUserRole',
                    $RoleModel->get(),
                    array(
                        'TextField' => 'Name',
                        'ValueField' => 'RoleID'
                    )
                );
                ?></li>
        </ul>
    </div>
<?php echo $this->Form->button('Start'); ?>
<?php echo $this->Form->close();
