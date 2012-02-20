<?php if (!defined('APPLICATION')) return; ?>
<script language="javascript">
   jQuery(document).ready(function($) {
      $('#Form_ReasonText').focus(function() {
         $('#Form_Reason2').attr('checked', 'checked');
      });
   });
</script>

<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Wrap">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<div class="Warning">
   <?php
   echo FormatString(T('You are about to ban {User.UserID,user}.'), $this->Data);
   ?>
</div>

<div class="P"><b><?php echo T('Why are you Banning this user?') ?></b></div>

<?php

echo '<div class="P">', $this->Form->Radio('Reason', 'Spamming', array('Value' => 'Spam')), '</div>';
echo '<div class="P">', $this->Form->Radio('Reason', 'Abusive Behavior', array('Value' => 'Abuse')), '</div>';
echo '<div class="P">', 
   $this->Form->Radio('Reason', 'Other', array('Value' => 'Other')),
   '<div class="TextBoxWrapper">',
   $this->Form->TextBox('ReasonText', array('MultiLine' => TRUE)),
   '</div>',
   '</div>';

echo '<div class="P">', $this->Form->CheckBox('DeleteContent', T("Also delete this user's content.")), '</div>';

?>


<?php
echo '<div class="Buttons P">', $this->Form->Button(T('Ban.Action', 'Ban')), '</div>';
echo $this->Form->Close();
?>
</div>