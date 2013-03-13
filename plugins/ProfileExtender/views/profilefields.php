<?php if (!defined('APPLICATION')) exit();

// Get posted values
$CustomLabels = GetValue('CustomLabel', $Sender->Form->FormValues(), array());
$CustomValues = GetValue('CustomValue', $Sender->Form->FormValues(), array());

// Write out the suggested fields first
if (count($this->ProfileFields) > 0)
   echo Wrap(Wrap(T('More Information'), 'h3'), 'li');
	
$CountFields = 0;
foreach ($this->ProfileFields as $Field) {
	$Value = $this->IsPostBack ? GetValue($CountFields, $CustomValues, '') : GetValue($Field, $this->UserFields, '');
	$CountFields++;
	echo '<li>';
		echo $Sender->Form->Hidden('CustomLabel[]', array('value' => $Field));
		echo $Sender->Form->Label($Field, 'CustomValue[]');
		echo $Sender->Form->TextBox('CustomValue[]', array('value' => $Value));
	echo '</li>';
}

if (CheckPermission('Plugins.ProfileExtender.Add')) : ?>

   <li>
   	<label><?php echo T('Custom Information'); ?></label>
   	<div><?php echo T('ProfileFieldsCustomDescription', 'Use these fields to create custom profile information. You can enter things like "Relationship Status", "Skype", or "Favorite Dinosaur". Be creative!'); ?></div>
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
   // Use stored or posted values?
   if ($this->IsPostBack)
      $FieldList = array_combine($CustomLabels, $CustomValues);
   else 
      $FieldList = $this->UserFields;
   
   // Write out user-defined custom fields
   foreach ($FieldList as $Field => $Value) {
      if (!in_array($Field, $this->ProfileFields)) {
         if ($this->IsPostBack) {
            $Field = GetValue($CountFields, $CustomLabels, '');
            $Value = GetValue($CountFields, $CustomValues, '');
         }
         $CountFields++;
         echo '<li>';
            echo $Sender->Form->TextBox('CustomLabel[]', array('value' => $Field, 'class' => 'CustomLabel'));
            echo $Sender->Form->TextBox('CustomValue[]', array('value' => $Value, 'class' => 'CustomValue'));
         echo '</li>';
      }
   }
   
   // Write out one empty row
   echo '<li>';
      echo $Sender->Form->TextBox('CustomLabel[]', array('class' => 'CustomLabel'));
      echo $Sender->Form->TextBox('CustomValue[]', array('class' => 'CustomValue'));
   echo '</li>';

endif;
