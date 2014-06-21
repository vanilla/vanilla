<?php if (!defined('APPLICATION')) exit();
$AllowEmbed = C('Garden.Embed.Allow');
?>
<style type="text/css">
.Info form ul {
    margin: 0;
    padding: 0;
}

.Info form li {
    list-style: none;
}

form em {
    font-size: 11px;
    color: #999;
}

p.WarningMessage {
    padding: 6px;
    margin-bottom: 20px;
}
</style>
<h1><?php echo T('Advanced Embed Settings'); ?></h1>
<div class="Info">
	<?php echo sprintf(T('The following settings apply to all types of embedded forum content: %s and %s.'), Anchor(T('blog comments'), 'embed/comments'), Anchor(T('forum embedding'), 'embed/forum'));
	if (!$AllowEmbed) {
	echo Wrap('<span style="background: #ff0;">'.T('Embedding is currently DISABLED.').'</span>', 'p');
	echo Anchor(T('Enable Embedding'), 'embed/advanced/enable/'.Gdn::Session()->TransientKey(), 'SmallButton');
	}
	else {
	echo Wrap('<span style="background: #ff0;">'.T('Embedding is currently ENABLED.').'</span>', 'p');
	echo Anchor(T('Disable Embedding'), 'embed/advanced/disable/'.Gdn::Session()->TransientKey(), 'SmallButton');
	?>
</div>
<h1><?php echo T('Settings'); ?></h1>
<div class="Info">
   <h2><?php echo T('Trusted Domains'); ?></h2>
   <p><?php echo T('Add a white-list of trusted domains.', 'You can optionally specify a white-list of trusted domains (ie. yourdomain.com) that are allowed to embed elements of your community (forum, comments, or modules).'); ?></p>
   <p><small>
      <strong><?php echo T('Notes:'); ?></strong>
      <?php echo T('Specify one domain per line, without protocol (ie. yourdomain.com).'); ?>
      <br /><?php echo T('The domain will include all subdomains (ie. yourdomain.com will also allow blog.yourdomain.com, news.yourdomain.com, etc.).'); ?>
      <br /><?php echo T('Leaving this input blank will mean that you allow embedding on any site, anywhere.'); ?>
   </small></p>
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();
   echo Wrap($this->Form->TextBox('Garden.TrustedDomains', array('MultiLine' => TRUE)), 'p');
   ?>
   
   <h2><?php echo T('Forum Embed Settings'); ?></h2>
   <p><?php echo T('The url where the forum is embedded:'); ?></p>
   <p><?php echo $this->Form->TextBox('Garden.Embed.RemoteUrl'); ?> <em><?php echo sprintf(T('Example: %s'), 'http://yourdomain.com/forum/'); ?></em></p>
   <p><?php echo $this->Form->CheckBox('Garden.Embed.ForceForum', "Force the forum to only be accessible through this url"); ?></p>
   <p><?php echo $this->Form->CheckBox('Garden.Embed.ForceDashboard', "Force the dashboard to only be accessible through this url <em>(not recommended)</em>"); ?></p>

   <h2>Sign In Settings</h2>
   <p><small>If you are using SSO you probably need to disable sign in popups.</small></p>
   <p><?php echo $this->Form->CheckBox('Garden.SignIn.Popup', "Use popups for sign in pages."); ?></p>
   <?php
   echo $this->Form->Close('Save', '', array('style' => 'margin: 0;')); 
   }
   ?>
</div>