<div class="StatsDisabled">
    <div class="alert alert-warning padded"><?php echo t("Vanilla Statistics are currently disabled"); ?></div>
    <?php
    if (!c('Garden.Analytics.Enabled', true)) {
        echo $this->Form->hidden('Allow', ['value' => 1]);
        echo "<p>".t("Garden.StatisticsDisabled", "You have specifically disabled Vanilla Statistics in your configuration file.")."</p>";
        echo $this->Form->button("Enable", ['class' => 'Button']);
    } else if (Gdn_Statistics::checkIsLocalhost() && !c('Garden.Analytics.AllowLocal', false)) {
        echo $this->Form->hidden('AllowLocal', ['value' => 1]);
        echo "<p>".t("Garden.StatisticsLocal.Explain", "This forum appears to be running in a test environment, or is otherwise reporting a private IP. By default, forums running on private IPs are not tracked.")."</p>";
        echo "<p>".t("Garden.StatisticsLocal.Resolve", "If you're sure your forum is accessible from the internet you can force it to report statistics here:")."</p>";
        echo $this->Form->button("Enable", ['class' => 'Button']);
    } else if (!$this->data('ConfWritable')) {
        echo "<p>".t("Garden.StatisticsReadonly.Explain", "Your config.php file appears to be read-only. This means that Vanilla will be unable to automatically register your forum's InstallationID and InstallationSecret.")."</p>";
        echo "<p>".t("Garden.StatisticsReadonly.Resolve", "To solve this problem, assign file mode 777 to your conf/config.php file.")."</p>";
    }
    ?>
</div>
