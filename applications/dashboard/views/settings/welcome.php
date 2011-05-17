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
      <a href="#"><img src="<?php echo Asset('applications/dashboard/design/images/welcome-screenshot.png'); ?>" alt="<?php echo T('Getting Started Video'); ?>" /></a>
   </div>
   <div class="Step">
      <h2><?php echo T('1. Encourage your friends to join your new community!'); ?></h2>
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
      <h2><?php echo T('2. Customize'); ?></h2>
      <p><?php echo T('Change colors, add your logo, and more...'); ?></p>
   </div>
   <div class="Step">
      <h2><?php echo T('3. Organize'); ?></h2>
      <p><?php echo T('Want to use many categories, or none at all? What do you want people to see when they first visit your community? Get started here...'); ?></p>
   </div>
   <div class="Step">
      <h2><?php echo T('4. Advanced Stuff'); ?></h2>
      <p><?php echo T('Embed parts of your community forum into your website to increase engagement...'); ?></p>
   </div>
</div>
<?php echo $this->Form->Close(); ?>