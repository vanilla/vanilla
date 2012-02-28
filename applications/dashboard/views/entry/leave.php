<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div id="Leave">
   <h1><?php echo T('Sign Out'); ?></h1>
   <div class="P">
   <?php if ($this->Leaving) { ?>
      <p class="Leaving"><?php echo T('SigningOut', 'Hang on a sec while we sign you out.'); ?></p>
   <?php } else if ($Session->IsValid()) { ?>
      <p><?php printf(T('AttemptingSignOut', 'You are attempting to sign out. Are you sure you want to %s?'), Anchor(T('sign out'), SignOutUrl())); ?></p>
   <?php } else { ?>
      <p><?php echo T('SignedOut', 'You are signed out.'); ?></p>
   <?php } ?>
   </div>
</div>
