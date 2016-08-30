<?php if (!defined('APPLICATION')) exit(); ?>
    <?php Gdn_Theme::assetBegin('Help');

    echo '<h2>'.sprintf(t('About %s'), t('Vanilla Statistics')).'</h2>';
    echo '<p>'.t("The Vanilla Statistics plugin turns your forum's dashboard into an analytics reporting tool",
            "Vanilla Statistics turns your forum's dashboard into an analytics reporting tool, allowing you to review activity on your forum over specific time periods. You can <a href=\"http://vanillaforums.org/docs/vanillastatistics\">read more about Vanilla Statistics</a> in our documentation.")
        .'</p>';
    ?>

    <div class="Help Aside">
        <?php
        echo '<h2>', t('Need More Help?'), '</h2>';
        echo '<ul>';
        echo '<li>', anchor(t('Vanilla Statistics Plugin'), '/settings/plugins#vanillastats-plugin'), '</li>';
        echo '<li>', anchor(t('Statistics Documentation'), 'http://docs.vanillaforums.com/addons/statistics/'), '</li>';
        echo '</ul>';
        ?>
    </div>
    <?php Gdn_Theme::assetEnd(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<?php if ($this->data('NotifyMessage') !== FALSE) { ?>
    <div class="padded alert alert-info">
        <?php
        echo "<strong>".t("Last time your forum communicated with the statistics server it received the following message:")."</strong>";
        echo "<p><i>".Gdn_Format::Html($this->data('NotifyMessage'))."</i></p>";
        ?>
    </div>
<?php } ?>
<?php
if ($this->data('AnalyticsEnabled')) {
    echo $this->fetchView('configuration', 'statistics', 'dashboard');
} else {
    echo $this->fetchView('disabled', 'statistics', 'dashboard');
}

echo $this->Form->close();
