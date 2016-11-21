<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<h1><?php echo $this->data('Title'); ?></h1>
<?php
if ($this->data('DefaultLocaleWarning')) {
    echo '<div class="Errors">', sprintf(t('Your default locale won\'t display properly', 'Your default locale won\'t display properly until it is enabled below. Please enable the following: %s.'), $this->data('MatchingLocalePacks')), '</div>';
}
echo $this->Form->open();
echo $this->Form->errors(); ?>
<div class="form-group">
    <?php echo
    '<div class="label-wrap-wide">'.$this->Form->label(t('Default Locale'), 'Locales').'</div> ';
    echo '<div class="input-wrap-right input-wrap-multiple">',
    $this->Form->dropDown('Locale', $this->data('Locales'), ['class' => '']),
    $this->Form->button('Save'),
    '</div>';
    ?>
</div>
<?php
echo $this->Form->close();
?>
<?php echo $this->Form->errors();
$this->addonType = 'locales';
include_once PATH_APPLICATIONS.'/dashboard/views/settings/plugins.php';
helpAsset(t('Need More Help?'), anchor(t('Internationalization & Localization'), 'http://docs.vanillaforums.com/developers/locales/'));
?>
