<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$CancelUrl = '/vanilla/discussions';
if (Gdn::Config('Vanilla.Categories.Use') === TRUE && $this->CategoryID > 0 && $this->CategoryData->NumRows() > 0) {
   foreach ($this->CategoryData->Result() as $Cat) {
      if ($Cat->CategoryID == $this->CategoryID) {
         $CancelUrl = '/vanilla/discussions/0/'.$Cat->CategoryID.'/'.Format::Url($Cat->Name);
         break;
      }      
   }
}
?>
<div id="DiscussionForm">
   <h2><?php echo property_exists($this, 'Discussion') ? GDN::Translate('Edit Discussion') : GDN::Translate('Start a New Discussion'); ?></h2>
   <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
      echo $this->Form->Label('Discussion Title', 'Name');
      echo $this->Form->TextBox('Name', array('maxlength' => 100));
      if (Gdn::Config('Vanilla.Categories.Use') === TRUE) {
         echo '<div class="Category">';
         echo $this->Form->Label('Category', 'CategoryID');
         echo $this->Form->DropDown('CategoryID', $this->CategoryData, array('TextField' => 'Name', 'ValueField' => 'CategoryID'));
         echo '</div>';
      }
      echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
      
      $Options = '';
      // If the user has any of the following permissions (regardless of junction), show the options
      // Note: I need to validate that they have permission in the specified category on the back-end
      // TODO: hide these boxes depending on which category is selected in the dropdown above.
      if ($Session->CheckPermission('Vanilla.Discussions.Announce'))
         $Options .= '<li>'.$this->Form->CheckBox('Announce', GDN::Translate('Announce this discussion'), array('value' => '1')).'</li>';

      if ($Session->CheckPermission('Vanilla.Discussions.Close'))
         $Options .= '<li>'.$this->Form->CheckBox('Closed', GDN::Translate('Close this discussion'), array('value' => '1')).'</li>';

      if ($Session->CheckPermission('Vanilla.Discussions.Sink'))
         $Options .= '<li>'.$this->Form->CheckBox('Sink', GDN::Translate('Sink this discussion'), array('value' => '1')).'</li>';
         
      if ($Options != '')
         echo '<ul class="PostOptions">' . $Options .'</ul>';

      echo $this->Form->Button((property_exists($this, 'Discussion')) ? 'Save' : 'Post Discussion');
      if (!property_exists($this, 'Discussion') || !is_object($this->Discussion) || (property_exists($this, 'Draft') && is_object($this->Draft))) {
         echo $this->Form->Button('Save Draft');
      }
      echo $this->Form->Button('Preview');
      $this->FireEvent('AfterFormButtons');
      echo Anchor(Gdn::Translate('Cancel'), $CancelUrl, 'Cancel');
      echo $this->Form->Close();
   ?>
</div>
