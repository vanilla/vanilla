<?php if (!defined('APPLICATION')) exit();
require_once $this->FetchViewLocation('helper_functions');
function TutLink($TutorialCode, $WriteTitle = TRUE, $ThumbnailSize = 'medium') {
   $Tutorial = GetTutorials($TutorialCode);
   if (!$Tutorial)
      return '';
   
   $Thumbnail = $ThumbnailSize == 'medium' ? $Tutorial['Thumbnail'] : $Tutorial['LargeThumbnail'];
   return Anchor(
      '<img src="'.$Thumbnail.'" alt="'.$Tutorial['Name'].'" title="'.$Tutorial['Name'].'" />'
      .($WriteTitle ? Wrap($Tutorial['Name']) : ''),
      'settings/tutorials/'.$Tutorial['Code']
   );
}
?>
<style type="text/css">
.Welcome {
	position: relative;
	min-height: 181px;
	background: #00346d;
	background:-webkit-gradient(linear, center bottom, center top, from(#014a8a), to(#00346d));
	background:-moz-linear-gradient(top, #00346d, #014a8a);
	-pie-background:linear-gradient(top, #00346d, #014a8a);
	background:linear-gradient(top, #00346d, #014a8a);
	padding: 20px 400px 20px 20px;
	color: #fff;
	border-radius: 4px;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
}
.Welcome strong {
	color: #FFF6CD;
}
/*
 Put this definition in admin.css b/c of the relative path to the bg image
.Welcome h2 {
	overflow: hidden;
	text-indent: -1000px;
	font-size: 1px;
	height: 42px;
	width: 341px;
	background: url('images/welcome-message.png') top left no-repeat transparent;
}
*/
.Welcome .Video {
	position: absolute;
	top: 10px;
	right: 10px;
   line-height: 1;
}
.Welcome a {
   color: #fff;
   text-decoration: underline;
}
.Welcome a:hover {
   text-decoration: none;
}
.Welcome .Video a {
   border: 10px solid #1c4c80;
   border-color: rgba(255, 255, 255, 0.1);
   background: #1c4c80;
   background: rgba(255, 255, 255, 0.1);
   line-height: 0;
   display: block;
}
.Welcome .Video a:hover {
   border: 10px solid #3c6ca0;
   border-color: rgba(255, 255, 255, 0.3);
}
.Welcome .Video img {
   width: 320px;
}
.Step {
	background: #efefef;
	border: 1px solid #dfdfdf;
	margin: 20px 0 0;
	padding: 16px 20px 10px;
	border-radius: 4px;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
	position: relative;
}
.NumberPoint {
	position: absolute;
	top: 0px;
	left: -16px;
	border-radius: 40px;
	-webkit-border-radius: 40px;
   -moz-border-radius: 40px;
   border: 5px solid #3CB3E8;
	font-size: 28px;
	height: 36px;
	width: 36px;
	background: #aee7fe;
	-moz-transform: rotate(-20.5deg);
	-o-transform: rotate(-20.5deg);
	-webkit-transform: rotate(-20.5deg);
	text-align: center;
	font-family: monospace,Arial,Sans-Serif;
	font-weight: bold;
	color: #003673;
	text-shadow: 1px 1px 0 rgba(256, 256, 256, 0.5);
	box-shadow: 0 1px 1px #003673;
	-moz-box-shadow: 0 1px 1px #003673;
	-webkit-box-shadow: 0 1px 1px #003673;
}
.Step textarea,
.Step .RecipientBox {
   font-family: arial;
   color: #666;
   font-size: 14px;
	width: 100%;
	padding: 3px;
	margin-bottom: 10px;
}
.Step textarea:focus,
.Step .RecipientBox:focus {
	color: #222;
}
.Step textarea {
	height: 85px;
}
.Step .RecipientBox {
	padding: 3px 1px;
}
.Step h2 {
	padding-left: 16px;
	font-size: 14px;
}
.Step .Videos {
	padding: 10px 0 0;
}
.Step .Videos a {
   vertical-align: top;
   display: inline-block;
   margin: 0 10px 10px 0;
   width: 212px;
}
.Step .Videos a:hover {
   background: #ddd;
}
.Step .Videos a img {
   border: 6px solid #ddd;
}
.Step .Videos a:hover img {
   border: 6px solid #bbb;
}
.Step .Videos a span {
   display: block;
   font-size: 11px;
   color: #555;
   padding: 0 6px 6px;
}
.Step .Videos a:hover span {
   color: #222;
}
#Content form .Step input.Button {
   margin: 0;
}
</style>
<h1><?php echo T('Getting Started with Vanilla'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info">
   <div class="Welcome">
      <h2><?php echo T('Getting Started with Vanilla'); ?></h2>
      <p><strong><?php echo T('Kick-start your community and increase user engagement.'); ?></strong></p>
      <p><?php echo T("Check out these tutorials to get started using Vanilla", "Vanilla is the simplest, most powerful community platform in the world. It's super-duper easy to use. Start with this introductory video and continue with the steps below. Enjoy!"); ?></p>
      <p><?php echo Anchor(T("Check out the full list of video tutorials here."), 'settings/tutorials'); ?></p>
      <div class="Video"><?php echo TutLink('introduction', FALSE, 'large'); ?></div>
   </div>
   <div class="Step">
      <div class="NumberPoint">1</div>
      <h2><?php echo T('The Basics'); ?></h2>
      <p><?php echo T('Learn how to use the basic functionality of your forum.'); ?></p>
      <div class="Videos">
         <?php
         echo TutLink('using-the-forum');
         echo TutLink('private-conversations');
         echo TutLink('user-profiles');
         ?>
      </div>
   </div>
   <div class="Step">
      <div class="NumberPoint">2</div>
      <h2><?php echo T("Appearance"); ?></h2>
      <p><?php echo T("Learn how to completely change your forum's look and feel: upload your logo, set your homepage, choose a theme and customize it."); ?></p>
      <div class="Videos">
         <?php echo TutLink('appearance'); ?>
      </div>
   </div>
   <div class="Step">
      <div class="NumberPoint">3</div>
      <h2><?php echo T('Organize'); ?></h2>
      <p><?php echo T('Create & organize discussion categories and manage your users.'); ?></p>
      <div class="Videos">
         <?php
         echo TutLink('user-registration');
         echo TutLink('users');
         echo TutLink('roles-and-permissions');
         echo TutLink('category-management-and-advanced-settings');
         ?>
      </div>
   </div>
   <div class="Step">
      <div class="NumberPoint">4</div>
      <h2><?php echo T('Encourage your friends to join your new community!'); ?></h2>
      <div class="TextBoxWrapper">
         <?php
         $Attribs = array('Multiline' => TRUE, 'class' => 'Message');
         if (!$this->Form->AuthenticatedPostBack())
            $Attribs['value'] = T('InvitationMessage',"Hi Pal!

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
</div>
<?php echo $this->Form->Close(); ?>