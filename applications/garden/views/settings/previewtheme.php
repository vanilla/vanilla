<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div>
   <strong>You are previewing the <em><?php echo $this->ThemeName; ?></em> theme.</strong>
   <div>
      <?php echo Anchor(Gdn::Translate('Apply'), 'settings/themes/'.$this->ThemeFolder.'/'.$Session->TransientKey(), 'Button'); ?>
      <?php echo Anchor(Gdn::Translate('Cancel'), 'settings/cancelpreview/', 'Button'); ?>
   </div>
</div>
<iframe src="<?php echo Url('/'); ?>" />