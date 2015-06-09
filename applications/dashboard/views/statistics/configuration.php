<style type="text/css">
    .Configuration {
        margin: 0 20px 20px;
        background: #f5f5f5;
        float: left;
    }

    .ConfigurationForm {
        border-right: 1px solid #aaa;
        padding: 20px;
        float: left;
    }

    .ConfigurationForm .InputBox {
        width: 350px;
    }

    #Content form .ConfigurationForm ul {
        padding: 0;
    }

    #Content form .ConfigurationForm input.Button {
        margin: 0;
    }

    .ConfigurationHelp {
        margin-left: 400px;
        padding: 20px;
    }

    .ConfigurationHelp strong {
        display: block;
    }

    .ConfigurationHelp img {
        width: 99%;
    }

    .ConfigurationHelp a img {
        border: 1px solid #aaa;
    }

    .ConfigurationHelp a:hover img {
        border: 1px solid #777;
    }

    .StatisticsVerification {
        padding: 10px;
        margin: 10px 0px;
    }

    .StatisticsOk {
        background: #d6ffcf;
        color: #06a800;
    }

    .StatisticsProblem {
        background: #ffd1d1;
        color: #c90000;
    }
</style>
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
