<?php if (!defined('APPLICATION')) exit();
	
// Write out the suggested fields first
   if (count($this->ProfileFields) > 0)
      echo Wrap(Wrap(T('More Information'), 'label'), 'li');
	
$CountFields = 0;
foreach ($this->ProfileFields as $Field) {
	$CountFields++;
	$Value = $this->IsPostBack ? GetValue($Field, $_POST, '') : GetValue($Field, $this->UserFields, '');
	echo '<li>';
		echo $this->Form->Hidden('CustomLabel[]', array('value' => $Field));
		echo $this->Form->Label($Field, 'CustomValue[]');
		echo $this->Form->TextBox('CustomValue[]', array('value' => $Value));
	echo '</li>';
}

if (CheckPermission('Plugins.ProfileExtender.Add')) : ?>

   <li>
   	<label><?php echo T('Custom Information'); ?></label>
   	<div><?php echo T('Use these fields to create custom profile information. You can enter things like "Relationship Status", "Skype", or "Favorite Dinosaur". Be creative!'); ?></div>
   	<div class="ProfileLabelContainer"><?php echo T('Label'); ?></div>
   	<div class="ProfileValueContainer"><?php echo T('Value'); ?></div>
   	<script type="text/javascript">
   		jQuery(document).ready(function($) {
   			$("input.CustomLabel").live('blur', function() {
   				var lastLabel = $('input.CustomLabel:last'),
   					lastVal = $('input.CustomValue:last');
   				
   				if (lastLabel.val() != '' || lastLabel.index() == $(this).index()) {
   					$(lastVal).after(lastVal.clone().val(''));
   					$(lastVal).after(lastLabel.clone().val(''));
   				}
   				return;
   			});
   		});
   	</script>
   	<style type="text/css">
   	.ProfileLabelContainer,
   	.ProfileValueContainer {
   		display: inline-block;
   		font-weight: bold;
   		width: 49%;
   	}
   	.CustomLabel,
   	.CustomValue {
   		width: 47%;
   		margin-bottom: 4px;
   	}
   	.CustomLabel {
   		margin-right: 10px;
   	}
   	</style>
   </li>

<?php
   // Write out user-defined custom fields
   $CustomLabel = GetValue('CustomLabel', $this->Form->FormValues(), array());
   $CustomValue = GetValue('CustomValue', $this->Form->FormValues(), array());
   foreach ($this->UserFields as $Field => $Value) {
      if (!in_array($Field, $this->ProfileFields)) {
         if ($this->IsPostBack) {
            $Field = GetValue($CountFields, $CustomLabel, '');
            $Value = GetValue($CountFields, $CustomValue, '');
         }
         $CountFields++;
         echo '<li>';
            echo $this->Form->TextBox('CustomLabel[]', array('value' => $Field, 'class' => 'CustomLabel'));
            echo $this->Form->TextBox('CustomValue[]', array('value' => $Value, 'class' => 'CustomValue'));
         echo '</li>';
      }
   }
   // Write out one empty row
   echo '<li>';
      echo $this->Form->TextBox('CustomLabel[]', array('class' => 'CustomLabel'));
      echo $this->Form->TextBox('CustomValue[]', array('class' => 'CustomValue'));
   echo '</li>';

endif;
