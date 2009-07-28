<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
echo $this->Form->Open();
?>
<h1><?php echo Gdn::Translate('Manage Messages'); ?></h1>
<div class="FilterMenu"><?php echo Anchor('Add Message', 'garden/messages/add', 'AddMessage'); ?></div>
<div class="Info"><?php echo Gdn::Translate('Messages can appear anywhere in your application, and can be used to inform your users of news and events. Use this page to re-organize your messages by dragging them up or down.'); ?></div>
<table id="MessageTable" border="0" cellpadding="0" cellspacing="0" class="AltColumns Sortable">
   <thead>
      <tr id="0">
         <th><?php echo Gdn::Translate('Location'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Message'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->MessageData->Result() as $Message) {
   $Message = $this->MessageModel->DefineLocation($Message);
   $Alt = $Alt ? FALSE : TRUE;
   ?>
   <tr id="<?php
      echo $Message->MessageID;
      echo $Alt ? '" class="Alt' : '';
   ?>">
      <td class="Info nowrap"><?php
         printf(
            Gdn::Translate('%1$s on %2$s'),
            ArrayValue($Message->AssetTarget, $this->_GetAssetData(), 'Custom Location'),
            ArrayValue($Message->Location, $this->_GetLocationData(), 'Custom Page')
         );
      ?><div>
         <strong><?php echo $Message->Enabled == '1' ? 'Enabled' : 'Disabled'; ?></strong>
         <span>|</span>
         <?php echo Anchor('Edit', '/garden/messages/edit/'.$Message->MessageID, 'EditMessage'); ?>
         <span>|</span>
         <?php echo Anchor('Delete', '/garden/messages/delete/'.$Message->MessageID.'/'.$Session->TransientKey(), 'DeleteMessage'); ?>
         </div>
      </td>
      <td class="Alt"><?php
         if ($Message->CssClass != '')
            echo '<div class="'.$Message->CssClass.'">';

         echo Format::Text($Message->Content);
         if ($Message->CssClass != '')
            echo '</div>';
      ?></td>
   </tr>
<?php } ?>
   </tbody>
</table>
<?php
echo $this->Form->Close();