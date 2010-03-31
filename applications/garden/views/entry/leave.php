<?php if (!defined('APPLICATION')) exit(); ?>
<div id="Leave">
   <h1><?php echo T('Sign Out'); ?></h1>
   <div class="Box">
   <?php if ($this->Leaving) { ?>
      <p class="Leaving"><?php echo T('Hang on a sec while we sign you out.'); ?></p>
   <?php } else { ?>
      <p><?php echo T('Failed to sign out of the application because of failed postback authentication'); ?></p>
   <?php } ?>
   </div>
</div>
