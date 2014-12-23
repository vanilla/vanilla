<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title') ?></h1>
<div class="">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<div class="P">
   <?php
//   echo $this->Form->Label();
     echo $this->Form->RadioList('Type', $this->Data('_Types'), array('list' => TRUE));
   ?>
</div>
   
<?php
echo '<div class="Buttons Buttons-Confirm">', 
   $this->Form->Button(T('OK')), ' ',
   $this->Form->Button(T('Cancel'), array('type' => 'button', 'class' => 'Button Close')),
   '</div>';
echo $this->Form->Close();
?>
</div>