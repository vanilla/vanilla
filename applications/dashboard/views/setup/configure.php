<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<div class="Title">
   <h1>
      <?php echo Img('applications/dashboard/design/images/vanilla_logo.png', array('alt' => 'Vanilla')); ?>
      <p><?php echo T('Version 2 Installer'); ?></p>
   </h1>
</div>
<div class="Form">
   <?php echo $this->Form->Errors(); ?>
   <ul>
      <li>
         <?php
            echo $this->Form->Label('Database Host', 'Database.Host');
            echo $this->Form->TextBox('Database.Host');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Database Name', 'Database.Name');
            echo $this->Form->TextBox('Database.Name');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Database User', 'Database.User');
            echo $this->Form->TextBox('Database.User');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Database Password', 'Database.Password');
            echo $this->Form->Input('Database.Password', 'password');
         ?>
      </li>
      <?php if ($this->Data('NoHtaccess')): ?>
      <li>
         <div class="Box"><?php echo T('You are missing Vanilla\'s <b>.htaccess</b> file. Sometimes this file isn\'t copied if you are using ftp to upload your files because this file is hidden. Make sure you\'ve copied the <b>.htaccess</b> file before continuing.'); ?></div>
         <?php
            echo $this->Form->CheckBox('SkipHtaccess', T('Install Vanilla without a .htaccess file.'));
         ?>
      </li>
      <?php endif; ?>
      <li class="Warning">
         <div>
         <?php
            echo T('Yes, the following information can be changed later.');
         ?>
         </div>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Application Title', 'Garden.Title');
            echo $this->Form->TextBox('Garden.Title');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Admin Email', 'Email');
            echo $this->Form->TextBox('Email');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Admin Username', 'Name');
            echo $this->Form->TextBox('Name');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Admin Password', 'Password');
            echo $this->Form->Input('Password', 'password');
         ?>
      </li>
      <li class="Last">
         <?php
            echo $this->Form->Label('Confirm Password', 'PasswordMatch');
            echo $this->Form->Input('PasswordMatch', 'password');
         ?>
      </li>
   </ul>
   <div class="Button">
      <?php echo $this->Form->Button('Continue &rarr;'); ?>
   </div>
</div>
<?php
echo $this->Form->Close();
