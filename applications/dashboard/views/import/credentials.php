<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
?>
<ul>
   <li>
   <?php
   echo $this->Form->Label('Email', 'Email'),
      $this->Form->TextBox('Email');

   echo $this->Form->Label('Password', 'Password'),
      $this->Form->Input('Password', 'password');
   ?>
   </li>
</ul>
<?php echo $this->Form->Close('OK');