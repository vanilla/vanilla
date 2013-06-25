<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
<h1 class="H"><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li class="User-Name">
      <?php
         echo $this->Form->Label('Username', 'Name');
         $Attributes = array();
         
         if (!$this->Data('_CanEditUsername')) {
            $Attributes['disabled'] = 'disabled';
         }
         echo $this->Form->TextBox('Name', $Attributes);
      ?>
   </li>
   
   <li class="User-Email">
      <?php
         echo $this->Form->Label('Email', 'Email');
         
         if (!$this->Data('_CanEditEmail') && UserModel::NoEmail()) {
            
            echo '<div class="Gloss">',
               T('Email addresses are disabled.', 'Email addresses are disabled. You can only add an email address if you are an administrator.'),
               '</div>';
            
         } else {
         
            $EmailAttributes = array();
            if (!$this->Data('_CanEditEmail')) {
               $EmailAttributes['disabled'] = 'disabled';
            }
            
            // Email confirmation
            if (!$this->Data('_EmailConfirmed'))
               $EmailAttributes['class'] = 'InputBox Unconfirmed';
            
            echo $this->Form->TextBox('Email', $EmailAttributes);
            
         }
      ?>
   </li>
   
   <?php if ($this->Data('_CanEditEmail')): ?>
   <li class="User-ShowEmail">
      <?php
         echo $this->Form->CheckBox('ShowEmail', T('Allow other members to see your email?'), array('value' => '1'));
      ?>
   </li>
   <?php endif ?>
   
   <?php if ($this->Data('_CanConfirmEmail')): ?>
   <li class="User-ConfirmEmail">
      <?php
         echo $this->Form->CheckBox('ConfirmEmail', T("Confirmed email address"), array('value' => '1'));
      ?>
   </li>
   <?php endif ?>
   
   <li class="Gender User-Gender">
      <?php
         echo $this->Form->Label('Gender', 'Gender');
         echo $this->Form->RadioList('Gender', $this->GenderOptions, array('default' => 'u'))
      ?>
   </li>
   
   <?php if (C('Garden.Profile.Titles', FALSE)): ?>
   <li class="User-Title">
      <?php
         echo $this->Form->Label('Title', 'Title');
         echo $this->Form->TextBox('Title');
      ?>
   </li>
   <?php endif; ?>
   
   <?php if (C('Garden.Profile.Locations', FALSE)): ?>
   <li class="User-Location">
      <?php
         echo $this->Form->Label('Location', 'Location');
         echo $this->Form->TextBox('Location');
      ?>
   </li>
   <?php endif; ?>
   
   <?php
      $this->FireEvent('EditMyAccountAfter');
   ?>
</ul>
<?php echo $this->Form->Close('Save', '', array('class' => 'Button Primary')); ?>
</div>
