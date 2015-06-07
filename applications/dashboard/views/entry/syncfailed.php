<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t("Account Sync Failed") ?></h1>
<div class="Info">
    <?php echo sprintf(t('Your remote login at <b>%1$s</b> was successful, but unfortunately there is already an account in this forum using your email address.
   Please contact support for assistance.'), $this->ProviderSite); ?>
</div>
