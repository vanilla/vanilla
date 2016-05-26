<div class="Configuration">
    <div class="ConfigurationForm">
        <ul>
            <li>
                <?php echo $this->Form->label('API Status'); ?>
                <div class="Slice Async" rel="statistics/verify"></div>
            </li>
            <li>
                <?php
                echo $this->Form->label('Application ID', 'InstallationID');
                echo $this->Form->textBox('InstallationID');
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Application Secret', 'InstallationSecret');
                echo $this->Form->textBox('InstallationSecret');
                ?>
            </li>
        </ul>
        <?php echo $this->Form->button('Save', array('class' => 'Button SliceSubmit')); ?>
    </div>
    <div class="ConfigurationHelp">
        <strong><?php echo t("About Vanilla Statistics"); ?></strong>

        <p><?php echo t("About.VanillaStatistics", "It is vitally important to the life of this free, open-source software that we accurately measure the reach and effectiveness of Vanilla. We ask that you please do not disable the reporting of this data."); ?></p>

        <p><?php echo t("About.DisableStatistics", "If you must disable this data reporting for some business reason, you can do so by adding the following line to your installation's configuration file: <code>\$Configuration['Garden']['Analytics']['Enabled'] = FALSE;</code>"); ?></p>
    </div>
</div>
