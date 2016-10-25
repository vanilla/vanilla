<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->data('Title'); ?></h1>
<?php

$desc = t(
    "The Vanilla Statistics plugin turns your forum's dashboard into an analytics reporting tool",
    "Vanilla Statistics turns your forum's dashboard into an analytics reporting tool, allowing you to review activity on your forum over specific time periods. You can <a href=\"http://docs.vanillaforums.com/help/addons/statistics\">read more about Vanilla Statistics</a> in our documentation."
);

$links = '<ul>';
$links .= '<li>'.anchor(t('Vanilla Statistics Plugin'), '/settings/plugins#vanillastats-plugin').'</li>';
$links .= '<li>'.anchor(t('Statistics Documentation'), 'http://docs.vanillaforums.com/addons/statistics/').'</li>';
$links .= '</ul>';

helpAsset(sprintf(t('About %s'), t('Vanilla Statistics')), $desc);
helpAsset(t('Need More Help?'), $links);

?>

<?php if ($this->data('NotifyMessage') !== FALSE) : ?>
    <div class="padded alert alert-info">
        <strong><?php echo t('Last time your forum communicated with the statistics server it received the following message:'); ?></strong>
        <p><i><?php echo Gdn_Format::html($this->data('NotifyMessage')); ?></i></p>
    </div>
<?php endif; ?>

<?php
    echo $this->Form->open();
    echo $this->Form->errors();
?>

<div class="js-form">
    <?php echo $this->fetchView($this->data('FormView'), 'statistics', 'dashboard'); ?>
</div>

<?php echo $this->Form->close(); ?>
