<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div>
   <strong>You are previewing the <em><?php echo $this->ThemeName; ?></em> theme.</strong>
   <div>
      <?php echo Anchor('Accept', 'settings/themes/'.$this->ThemeFolder.'/'.$Session->TransientKey(), 'Button'); ?>
      <?php echo Anchor('Cancel', 'settings/cancelpreview/', 'Button'); ?>
   </div>
</div>
<iframe src="<?php echo Url('/garden/settings/themes'); ?>" />