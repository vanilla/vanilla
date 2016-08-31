<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->data('Title'); ?></h1>
<?php Gdn_Theme::assetBegin('Help'); ?>
    <h2><?php echo sprintf(t('About %s'), t('Vanilla Statistics')); ?></h2>
    <p><?php echo t(
        "The Vanilla Statistics plugin turns your forum's dashboard into an analytics reporting tool",
        "Vanilla Statistics turns your forum's dashboard into an analytics reporting tool, allowing you to review activity on your forum over specific time periods. You can <a href=\"http://vanillaforums.org/docs/vanillastatistics\">read more about Vanilla Statistics</a> in our documentation."
    ); ?></p>

    <div class="Help Aside">
        <h2><?php echo t('Need More Help?'); ?></h2>
        <ul>
            <li><?php echo anchor(t('Vanilla Statistics Plugin'), '/settings/plugins#vanillastats-plugin'); ?></li>
            <li><?php echo anchor(t('Statistics Documentation'), 'http://docs.vanillaforums.com/addons/statistics/'); ?></li>
        </ul>
    </div>
<?php Gdn_Theme::assetEnd(); ?>


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
