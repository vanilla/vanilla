<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div id="Leave">
   <h1><?php echo T('Sign Out'); ?></h1>
   <div class="Box">
      <p id="LeavingWrap" class="Leaving" <?php if (!$this->Leaving) echo 'style="display: none;"'; ?>><?php echo T('SigningOut', 'Hang on a sec while we sign you out.'); ?> <span class="TinyProgress"></span></p>
   
      <?php if (!$this->Leaving): ?>
      
      <?php if ($Session->IsValid()) { ?>
      <p id="SignoutWrap">
         <script language="javascript">
            jQuery(document).ready(function($) {
               var url = $('#SignoutLink').attr('href');
               if (url) {
                  $('#SignoutWrap').hide();
                  $('#LeavingWrap').show();
                  window.location.replace(url);
               }
            });
         </script>
         <?php printf(T('AttemptingSignOut', 'You are attempting to sign out. Are you sure you want to %s?'), Anchor(T('sign out'), SignOutUrl($this->Data('Target')), '', array('id' => 'SignoutLink'))); ?>
      </p>
      <?php } else { ?>
         <p><?php echo T('SignedOut', 'You are signed out.'); ?></p>
      <?php } ?>
      
      <?php endif; ?>   
   </div>
</div>
