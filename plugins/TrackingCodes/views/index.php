<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$TrackingCodes = C('Plugins.TrackingCodes.All');
if (!is_array($TrackingCodes))
   $TrackingCodes = array();

?>
<h1><?php echo T('Tracking Codes'); ?></h1>
<div class="Info"><?php echo T('Tracking codes are added to every page just above the closing &lt;/body&gt; tag. Useful for common tracking code generators like Google Analytics, Hubspot, etc. Add, edit and enable/disable them below.'); ?></div>
<div class="FilterMenu"><?php echo Anchor(T('Add Tracking Code'), 'dashboard/settings/trackingcodes/edit', 'AddTrackingCode SmallButton'); ?></div>
<?php if (count($TrackingCodes) > 0) { ?>
<table id="MessageTable" border="0" cellpadding="0" cellspacing="0" class="AltColumns Sortable">
   <thead>
      <tr id="0">
         <th><?php echo T('Tracking Code'); ?></th>
         <th class="Alt"><?php echo T('State'); ?></th>
         <th><?php echo T('Options'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($TrackingCodes as $Index => $Code) {
   $Key = GetValue('Key', $Code, '');
   $Alt = $Alt ? FALSE : TRUE;
   ?>
   <tr id="<?php
      echo $Index;
      echo $Alt ? '" class="Alt' : '';
   ?>">
      <td class="Info nowrap"><strong><?php echo GetValue('Name', $Code, 'Undefined'); ?></strong></td>
      <td class="Alt"><?php echo GetValue('Enabled', $Code) == '1' ? 'Enabled' : 'Disabled'; ?></td>
      <td>
         <?php
         echo Anchor(T(GetValue('Enabled', $Code) == '1' ? 'Disable' : 'Enable'), '/dashboard/settings/trackingcodes/toggle/'.$Key.'/'.$Session->TransientKey(), 'ToggleCode SmallButton');
         echo Anchor(T('Edit'), '/dashboard/settings/trackingcodes/edit/'.$Key, 'EditCode SmallButton');
         echo Anchor(T('Delete'), '/dashboard/settings/trackingcodes/delete/'.$Key.'/'.$Session->TransientKey(), 'PopConfirm SmallButton');
         ?>
         </div>
      </td>
   </tr>
<?php } ?>
   </tbody>
</table>
<?php } ?>