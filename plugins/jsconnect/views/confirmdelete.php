<?php if (!defined('APPLICATION')) exit(); ?>
<style>
   .Popup .Buttons {
      text-align: center;
   }
   div.Popup input.Button {
      min-width: 50px;
      margin-left: 10px;
   }
   .Popup .Button:first-child {
      margin-left: 0;
   }
</style>
<?php
echo '<h1>', T('Please Confirm'), '</h1>';
echo $this->Form->Open(array('id' => 'ConfirmForm'));
echo $this->Form->Errors();

echo '<p>'.T('Are you sure you want to delete this?').'</p>';


echo '<div class="Buttons">';

echo $this->Form->Button('Yes').
   ' '.
   $this->Form->Button('No');

echo '</div>';

echo $this->Form->Close();
