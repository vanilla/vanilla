<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::Session();
$PreviewThemeName = $Session->GetPreference('PreviewThemeName', 'default');
$PreviewThemeFolder = $Session->GetPreference('PreviewThemeFolder', 'default');
?>
<div class="PreviewTheme">
   <p>You are previewing the <em><?php echo $PreviewThemeName; ?></em> theme.</p>
   <div class="PreviewButtons">
      <?php echo Anchor(T('Apply'), 'settings/themes/'.$PreviewThemeFolder.'/'.$Session->TransientKey(), 'PreviewButton'); ?>
      <?php echo Anchor(T('Cancel'), 'settings/cancelpreview/', 'PreviewButton'); ?>
   </div>
</div>