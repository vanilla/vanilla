<?php
if ($this->data('StatisticsVerified')) {
?>
    <div class="StatisticsVerification StatisticsOk"><?php echo t("Verified!"); ?></div>
<?php
} else {
?>
    <?php echo $this->Form->open(['action' => url('/statistics')]); ?>
    <div class="StatisticsVerification StatisticsProblem">
        <?php echo t("Problem with credentials."); ?>
        <p><?php echo $this->Form->button('Re-Register API Key', array('class' => 'SmallButton', 'name' => 'Reregister')); ?></p>
    </div>
    <?php echo $this->Form->close(); ?>
<?php
}
