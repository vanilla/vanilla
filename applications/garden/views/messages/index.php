<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
echo $this->Form->Open();
?>
<h1><?php echo Gdn::Translate('Manage Messages'); ?></h1>
<div class="FilterMenu"><?php echo Anchor('Add Message', 'garden/messages/add', 'AddMessage'); ?></div>
<div class="Info"><?php echo Gdn::Translate('Messages can appear anywhere in your application, and can be used to inform your users of news and events.'); ?></div>
<table border="0" cellpadding="0" cellspacing="0" class="AltRows">
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Location'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Message'); ?></th>
         <th><?php echo Gdn::Translate('Enabled'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->MessageData->Result() as $Message) {
   $Alt = $Alt ? FALSE : TRUE;
   ?>
   <tr class="More<?php echo $Alt ? ' Alt' : ''; ?>">
      <td><?php
         echo ArrayValue($Message->Controller, $this->_GetControllerData(), 'Unknown Page');
         echo '&nbsp;';
         echo ArrayValue($Message->AssetTarget, $this->_GetAssetData(), 'Unknown Location');
      ?></td>
      <td class="Alt"><?php echo Format::Text($Message->Content); ?></td>
      <td><?php echo $Message->Enabled == '1' ? 'yes' : 'no'; ?></td>
   </tr>
   <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
      <td colspan="3" class="Info">
         <?php echo Anchor('Edit', '/garden/messages/edit/'.$Message->MessageID, 'EditMessage'); ?>
         <span>|</span>
         <?php echo Anchor('Delete', '/garden/messages/delete/'.$Message->MessageID.'/'.$Session->TransientKey(), 'DeleteMessage'); ?>
      </td>
   </tr>
<?php } ?>
   </tbody>
</table>
<?php
echo $this->Form->Close();