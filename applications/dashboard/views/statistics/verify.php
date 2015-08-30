<div class="Slice" rel="statistics/verify">
    <?php if ($this->data('StatisticsVerified')) { ?>
        <div class="StatisticsVerification StatisticsOk"><?php echo t("Verified!"); ?></div>
    <?php } else { ?>
        <div class="StatisticsVerification StatisticsProblem">
            <?php echo t("Problem with credentials."); ?>
            <?php // echo $this->Form->Hidden('ClearCredentials',array('value'=>1)); ?>
            <p><?php echo $this->Form->button('Re-Register API Key', array('class' => 'SmallButton', 'name' => 'Reregister')); ?></p>
        </div>
    <?php } ?>
</div>
