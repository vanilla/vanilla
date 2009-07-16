<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<div class="Title">
   <h1><span>Vanilla</span></h1>
   <h2><?php echo Gdn::Translate("Fill out this form and you'll be tasting Vanilla in moments!"); ?></h2>
</div>
<?php
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Database Host', 'Database.Host');
         echo $this->Form->TextBox('Database.Host');
      ?>
   </li>
   <li class="Warning">
      <div>
      <?php
         echo Gdn::Translate('Note: If you are upgrading from a Vanilla 1 installation, use your existing Vanilla 1 database name below.');
      ?>
      </div>
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
   <li class="Warning">
      <div>
      <?php
         echo Gdn::Translate('Fret not, the next four inputs can be changed later if you want.');
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
   <?php echo $this->Form->Close('Continue'); ?>
</div>