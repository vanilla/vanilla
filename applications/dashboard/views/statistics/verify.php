<?php
if ($this->data('StatisticsVerified')) {
?>
    <div class="StatisticsVerification text-success"><?php echo t("Verified!"); ?></div>
<?php
} else {
?>
    <?php echo $this->Form->open(['action' => url('/statistics')]); ?>
    <div class="StatisticsVerification text-danger">
        <?php echo t("Problem with credentials."); ?>
        <?php echo $this->Form->button('Re-Register API Key', ['class' => 'padded-left btn btn-primary', 'name' => 'Reregister']); ?>
    </div>
    <?php echo $this->Form->close(); ?>
<?php
}
