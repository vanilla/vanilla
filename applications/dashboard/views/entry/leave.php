<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<div id="Leave">
    <h1><?php echo t('Sign Out'); ?></h1>

    <div class="P">
        <?php if ($this->Leaving) { ?>
            <p class="Leaving"><?php echo t('SigningOut', 'Hang on a sec while we sign you out.'); ?></p>
        <?php } else if ($Session->isValid()) { ?>
            <p><?php printf(t('AttemptingSignOut', 'You are attempting to sign out. Are you sure you want to %s?'), anchor(t('sign out'), signOutUrl())); ?></p>
        <?php } else { ?>
            <p><?php echo t('SignedOut', 'You are signed out.'); ?></p>
        <?php } ?>
    </div>
</div>
