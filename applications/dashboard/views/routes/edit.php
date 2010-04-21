<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php
   if ($this->Route !== FALSE)
      echo T('Edit Route');
   else
      echo T('Add Route');
?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Route Expression', 'Route');
         $Attributes = array();
         if (in_array($this->Route, $this->ReservedRoutes)) {
            $Attributes['value'] = $this->Route;
            $Attributes['disabled'] = 'disabled';
         }
         
         echo $this->Form->TextBox('Route', $Attributes);
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Target', 'Target');
         echo $this->Form->TextBox('Target');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save'); ?>