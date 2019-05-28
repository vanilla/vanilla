<?php if (!defined('APPLICATION')) exit();
$ConnectSource = $this->Form->getFormValue('ProviderName');
?>
<div class="Connect FormTitleWrapper AjaxForm">
    <h1><?php echo stringIsNullOrEmpty($ConnectSource) ? t("Sign In") : sprintf(t('%s Connect'), htmlentities($ConnectSource)); ?></h1>

    <div class="FormWrapper">
        <?php
        echo $this->Form->open();
        echo $this->Form->errors();
        echo $this->Form->close();
        ?>
    </div>
</div>
