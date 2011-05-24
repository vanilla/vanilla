<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Getting Started with Vanilla'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info">
   <div class="Welcome">
      <h2><?php echo T('Getting Started with Vanilla'); ?></h2>
      <p><strong><?php echo T('Kick-start your community and increase user engagement.'); ?></strong></p>
      <p><?php echo T("Vanilla is the simplest, most powerful community platform in the world. It's super duper easy to use. We suggest you start by watching this introductory video and continue with the steps below. Enjoy!"); ?></p>
      <a href="http://www.screenr.com/73r" target="_blank"><img src="<?php echo Asset('applications/dashboard/design/images/welcome-screenshot.png'); ?>" title="<?php echo T('Introduction to Vanilla'); ?>" /></a>
   </div>
   <div class="Step">
      <div class="NumberPoint">1</div>
      <h2><?php echo T('Encourage your friends to join your new community!'); ?></h2>
      <div class="TextBoxWrapper">
         <?php
         $Attribs = array('Multiline' => TRUE, 'class' => 'Message');
         if (!$this->Form->AuthenticatedPostBack())
            $Attribs['value'] = T("Hi Pal!

Check out the new community forum I've just set up. It's a great place for us to chat with each other online.

Follow the link below to log in.");
         echo $this->Form->TextBox('InvitationMessage', $Attribs);
         echo $this->Form->TextBox('Recipients', array('class' => 'RecipientBox'));
         ?>
      </div>
      <script type="text/javascript">
      jQuery(document).ready(function($) {
         if ($('input.RecipientBox').val() == '')
            $('input.RecipientBox').val("<?php echo $this->TextEnterEmails; ?>");
            
         $('input.RecipientBox').focus(function() {
            if ($(this).val() == "<?php echo $this->TextEnterEmails; ?>")
               $(this).val('');
         });
         $('input.RecipientBox').blur(function() {
            if ($(this).val() == '')
               $(this).val("<?php echo $this->TextEnterEmails; ?>");
         });
      });
      </script>
      <?php echo $this->Form->Button(T('Send Invitations!')); ?>
   </div>
   <div class="Step">
      <div class="NumberPoint">2</div>
      <h2><?php echo T('Customize'); ?></h2>
      <p><?php echo T('Define your forum homepage, upload your logo, and more...'); ?></p>
      <div class="Videos">
         <a href="http://www.screenr.com/Qb0" target="_blank"><img src="<?php echo Asset('applications/dashboard/design/images/welcome-vid-banner.png'); ?>" title="<?php echo T('Change your banner'); ?>" /></a>
         <a href="http://www.screenr.com/iCE" target="_blank"><img src="<?php echo Asset('applications/dashboard/design/images/welcome-vid-homepage.png'); ?>" title="<?php echo T('Define your forum homepage'); ?>" /></a>
         <a href="http://www.screenr.com/Vb0" target="_blank"><img src="<?php echo Asset('applications/dashboard/design/images/welcome-vid-themes.png'); ?>" title="<?php echo T('How to use themes'); ?>" /></a>
      </div>
   </div>
   <div class="Step">
      <div class="NumberPoint">3</div>
      <h2><?php echo T('Organize'); ?></h2>
      <p><?php echo T('Create & organize discussion categories, manage your users, and more...'); ?></p>
      <div class="Videos">
         <a href="http://www.screenr.com/CfE" target="_blank"><img src="<?php echo Asset('applications/dashboard/design/images/welcome-vid-categories.png'); ?>" title="<?php echo T('Organize discussion categories'); ?>" /></a>
         <a href="http://www.screenr.com/kwG" target="_blank"><img src="<?php echo Asset('applications/dashboard/design/images/welcome-vid-users.png'); ?>" title="<?php echo T('Manage users'); ?>" /></a>
      </div>
   </div>
   <div class="Step">
      <div class="NumberPoint">4</div>
      <h2><?php echo T('Advanced Stuff'); ?></h2>
      <p><?php echo T('Embed your community forum into your website to increase engagement...'); ?></p>
      <div class="Videos">
         <a href="http://www.screenr.com/y4D" target="_blank"><img src="<?php echo Asset('applications/dashboard/design/images/welcome-vid-embed.png'); ?>" title="<?php echo T('Embed your forum in your web site'); ?>" /></a>
      </div>
   </div>
</div>
<?php echo $this->Form->Close(); ?>