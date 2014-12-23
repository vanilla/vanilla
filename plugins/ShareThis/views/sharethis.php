<?php if (!defined('APPLICATION')) exit(); ?>

<style type="text/css">
.Configuration {
   margin: 0 20px 20px;
   background: #f5f5f5;
   float: left;
}
.ConfigurationForm {
   padding: 20px;
   float: left;
}
#Content form .ConfigurationForm ul {
   padding: 0;
}
#Content form .ConfigurationForm input.Button {
   margin: 0;
}
.ConfigurationHelp {
   border-left: 1px solid #aaa;
   margin-left: 340px;
   padding: 20px;
}
.ConfigurationHelp strong {
    display: block;
    font-size: 14px;
    font-weight: bold;
}
.ConfigurationHelp img {
   width: 99%;
}
.ConfigurationHelp a img {
    border: 1px solid #aaa;
}
.ConfigurationHelp a:hover img {
    border: 1px solid #777;
}
</style>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info">
   <?php echo T('This plugin adds ShareThis buttons to the bottom of each post. ShareThis is the largest social bookmarking tool on the web. It allows you to add twitter, facebook, and pretty much any social network you can think of, share buttons to your content. <br /><br /> Share this is free to use. You do not need a publisher account for this plugin to work. If you do not have, or want a publisher account please leave the field below blank.<br /><br />You can register for a free ShareThis publisher account which gives you support, publishing tools, and analytics.<br /><br /> <a href="http://sharethis.com/register" target="_blank">Register with ShareThis for a publishers account</a>'); ?>
</div>

<div class="Configuration">
   <div class="ConfigurationForm">
      <ul>
         <li>
            <?php
         		echo $this->Form->Label("Enter ShareThis Publisher Number");
         		echo $this->Form->TextBox('Plugin.ShareThis.PublisherNumber');
      		?>
         </li>
         <li>
            <?php
         		echo $this->Form->Label("Enter 'via' handle");
         		echo $this->Form->TextBox('Plugin.ShareThis.ViaHandle');
      		?>
         </li>
         <li>
            <?php
         		echo $this->Form->CheckBox('Plugin.ShareThis.CopyNShare', "Enable 'CopyNShare' functionality");
      		?>
         </li>
      </ul>
      <?php echo $this->Form->Button('Save', array('class' => 'Button SliceSubmit')); ?>
   </div>
   <div class="ConfigurationHelp">
      <strong> ShareThis in Action!</strong>
		<p><?php echo Anchor(Img('/plugins/ShareThis/design/sharethis_screen.png', array('style' => 'max-width: 645px;'))); ?></p>
		<p><strong>Using ShareThis with Vanilla Social Connect</strong>If you are using the <a href="http://vanillaforums.com/features/social-connect" target="_blank">Social Connect</a> plugin to allow your community members to sign in with Facebook or Twitter, the ShareThis plugin will automatically retrieve their information for seamless sharing. </p>
   </div>
</div>
<?php
   echo $this->Form->Close();


