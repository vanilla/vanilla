<style type="text/css">
    .StatsDisabled {
        margin: 0 20px 20px;
        padding: 20px;
        background: #f2f2f2;
        color: #C90000;
    }

    .StatsDisabled strong {
        font-size: 18px;

    }

    #Content form .StatsDisabled input.Button {
        margin: 20px 0 0 0;
    }
</style>
<div class="StatsDisabled">
    <strong><?php echo t("Vanilla Statistics are currently disabled"); ?></strong>
    <?php
    if (!c('Garden.Analytics.Enabled', true)) {
        echo $this->Form->Hidden('Allow', array('value' => 1));
        echo "<p>".t("Garden.StatisticsDisabled", "You have specifically disabled Vanilla Statistics in your configuration file.")."</p>";
        echo $this->Form->button("Enable", array('class' => 'Button SliceSubmit'));
    } else if (Gdn_Statistics::CheckIsLocalhost() && !c('Garden.Analytics.AllowLocal', false)) {
        echo $this->Form->Hidden('AllowLocal', array('value' => 1));
        echo "<p>".t("Garden.StatisticsLocal.Explain", "This forum appears to be running in a test environment, or is otherwise reporting a private IP. By default, forums running on private IPs are not tracked.")."</p>";
        echo "<p>".t("Garden.StatisticsLocal.Resolve", "If you're sure your forum is accessible from the internet you can force it to report statistics here:")."</p>";
        echo $this->Form->button("Enable", array('class' => 'Button SliceSubmit'));
    } else if (!$this->data('ConfWritable')) {
        echo "<p>".t("Garden.StatisticsReadonly.Explain", "Your config.php file appears to be read-only. This means that Vanilla will be unable to automatically register your forum's InstallationID and InstallationSecret.")."</p>";
        echo "<p>".t("Garden.StatisticsReadonly.Resolve", "To solve this problem, assign file mode 777 to your conf/config.php file.")."</p>";
    }
    ?>
    <p></p>

</div>
