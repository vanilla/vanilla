<?php if (!defined('APPLICATION')) exit();
$CategoryData = GetValue('CategoryData', $this->Data);
?>
<h1><?php echo T($this->Data('Title')); ?></h1>
<div class="Info">
   <?php echo T('Let your users report bad content and tag awesome content in your community.'); ?>
</div>

<?php
   // Settings
   echo $this->Form->Open();
   echo $this->Form->Errors();
   
   $Features = array(
      'report'    => array('Reporting Bad Content', "reports bad", "Find out who is posting objectionable content, where they're they're posting it, and take action."),
      'awesome'   => array('Tagging Good Content', "tags awesome", "Get notified when people post great threads. Reward these people, and use their content to promote your site.")
   );
   
   $ReportingData = GetValue('Plugins.Reporting.Data', $this->Data);
?>

<table id="ReportingTable" border="0" cellpadding="0" cellspacing="0" class="AltColumns">
   <thead>
      <tr>
         <th><?php echo T('Feature'); ?></th>
         <th class="Alt"><?php echo T('Description'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php

$Alt = FALSE;
$ActionURL = 'plugin/reporting/feature/%s/%s?TransientKey='.Gdn::Session()->TransientKey();
foreach ($Features as $Feature => $FeatureDesc) {
   $Alt = $Alt ? FALSE : TRUE;
   list($FeatureName, $FeatureVerb, $FeatureDescription) = $FeatureDesc;
   
   $FeatureKey = ucfirst($Feature).'Enabled';
   $FeatureEnabled = GetValue($FeatureKey, $ReportingData);
   
   $FeatureActionKey = ucfirst($Feature).'Action';
   $FeatureAction = GetValue($FeatureActionKey, $ReportingData);
   
   ?>
   <tr <?php echo ($Alt ? 'class="Alt"' : ''); ?>>
      <td class="Info nowrap"><?php echo $FeatureName; ?>
         <div>
         <strong><?php echo $FeatureEnabled ? 'Enabled' : 'Disabled'; ?></strong>
         <?php
            $ButtonAction = $FeatureEnabled ? 'disable': 'enable';
            $ButtonURL = sprintf($ActionURL, $Feature, $ButtonAction);
            echo Anchor(T(ucfirst($ButtonAction)), $ButtonURL, 'ToggleFeature SmallButton');
         ?>
         </div>
      </td>
      <td class="Alt"><?php echo Gdn_Format::Text($FeatureDescription); ?></td>
   </tr>
   
<?php } ?>

   </tbody>
</table>