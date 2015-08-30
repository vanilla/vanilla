<?php if (!defined('APPLICATION')) exit();
$ConnectSource = $this->Form->getFormValue('ProviderName');
?>
<div class="Connect">
    <h1><?php echo stringIsNullOrEmpty($ConnectSource) ? t("Sign in") : sprintf(t('%s Connect'), $ConnectSource); ?></h1>

    <div>
        <?php
        echo $this->Form->open();
        echo $this->Form->errors();
        echo $this->Form->close();
        ?>
    </div>
</div>
