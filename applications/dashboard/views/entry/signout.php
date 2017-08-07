<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<div id="Leave">
    <h1><?php echo t('Sign Out'); ?></h1>

    <div class="Box">
        <p id="LeavingWrap"
           class="Leaving" <?php if (!$this->Leaving) echo 'style="display: none;"'; ?>><?php echo t('SigningOut', 'Hang on a sec while we sign you out.'); ?>
            <span class="TinyProgress"></span></p>

        <?php if (!$this->Leaving): ?>

            <?php if ($Session->isValid()) { ?>
                <p id="SignoutWrap">
                    <script>
                        jQuery(document).ready(function($) {
                            var url = $('#SignoutLink').attr('href');
                            if (url) {
                                $('#SignoutWrap').hide();
                                $('#LeavingWrap').show();
                                window.location.replace(url);
                            }
                        });
                    </script>
                    <?php printf(t('AttemptingSignOut', 'You are attempting to sign out. Are you sure you want to %s?'), anchor(t('sign out'), signOutUrl($this->data('Target')).'&override='.$this->data('Override', '0'), '', ['id' => 'SignoutLink'])); ?>
                </p>
            <?php } else { ?>
                <p><?php echo t('SignedOut', 'You are signed out.'); ?></p>
            <?php } ?>

        <?php endif; ?>
    </div>
</div>
