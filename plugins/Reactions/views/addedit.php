<?php if (!defined('APPLICATION')) exit();

echo $this->Form->open();
echo $this->Form->errors();
?>
<h1><?php echo t($this->title()); ?></h1>
<div class="AddReaction">
   <ul class="Reaction">
      <li class="Name row form-group">
         <?php echo $this->Form->labelWrap('Name'); ?>
         <?php echo $this->Form->textBoxWrap('Name'); ?>
      </li>

      <li class="Description row form-group">
         <?php echo $this->Form->labelWrap('Description'); ?>
         <?php echo $this->Form->textBoxWrap('Description', ['Table' => 'Reaction', 'MultiLine' => true]); ?>
      </li>

      <li class="Class row form-group">
         <?php echo $this->Form->labelWrap('Class'); ?>
         <?php echo $this->Form->textBoxWrap('Class'); ?>
      </li>

      <li class="Points row form-group">
         <?php echo $this->Form->labelWrap('Points'); ?>
         <?php echo $this->Form->textBoxWrap('Points'); ?>
      </li>

       <?php if (!is_null($this->Form->formData()['LogThreshold']) && strtolower($this->Data['Reaction']['Class']) === 'flag') { ?>
        <li class="LogThreshold row form-group">
           <?php echo $this->Form->labelWrap("Moderation Threshold"); ?>
           <?php echo $this->Form->textBoxWrap("LogThreshold", ["Table" => "Reaction"]); ?>
       </li>
       <?php } ?>

       <?php if (!is_null($this->Form->formData()['RemoveThreshold']) && strtolower($this->Data['Reaction']['Class']) === 'flag') { ?>
           <li class="RemoveThreshold row form-group">
               <?php echo $this->Form->labelWrap("Removal Threshold"); ?>
               <?php echo $this->Form->textBoxWrap("RemoveThreshold", ["Table" => "Reaction"]); ?>
           </li>
       <?php } ?>

   </ul>
   <?php echo $this->Form->close('Save'); ?>
</div>
