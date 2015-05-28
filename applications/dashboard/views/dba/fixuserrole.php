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

   <h1><?php echo T('Fix User Roles'); ?></h1>
<?php echo $this->Form->Open(); ?>

   <div class="Info">
      <?php if ($this->Data('CompletedFix')) : ?>
         <p>
            <strong><?php echo T('Operation completed successfully'); ?></strong>
         </p>
      <?php endif; ?>

      <p>
         <?php echo T('All users with an invalid or no role will be updated with the following role assignment.'); ?>
      </p>

      <?php echo $this->Form->Errors(); ?>
   </div>
   <div>
      <ul>
         <li><?php
            $RoleModel = new RoleModel();
            echo $this->Form->Label('Default User Role', 'DefaultUserRole');
            echo $this->Form->DropDown(
               'DefaultUserRole',
               $RoleModel->Get(),
               array(
                  'TextField' => 'Name',
                  'ValueField' => 'RoleID'
               )
            );
         ?></li>
      </ul>
   </div>
<?php echo $this->Form->Button('Start'); ?>
<?php echo $this->Form->Close();
