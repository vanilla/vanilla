<?php if (!defined('APPLICATION')) exit();
$this->EmbedType = GetValue('0', $this->RequestArgs, 'wordpress');
$AllowEmbed = C('Garden.Embed.Allow');
?>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo Wrap(Anchor(T("Introducing Vanilla Comments"), 'http://vanillaforums.com/blog/news/introducing-vanilla-comments/'), 'li');
   echo Wrap(Anchor(htmlspecialchars(T("Converting from the <Embed> Vanilla Plugin")), 'http://vanillaforums.com/blog/converting-embed-plugin/'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Embed Forum'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info">
   <?php
   echo 'Your entire vanilla forum can be embedded into another page. This is typically done so you can insert your forum into another site with minimal theming effort. The preferred method is to ';
   echo Anchor('customize your theme', 'settings/themes');
   echo ' to match your existing website.';
   if (!$AllowEmbed) {
      echo Wrap('<span style="background: #ff0;">'.T('Embedding is currently DISABLED.').'</span>', 'p');
      echo Anchor('Enable Embedding', 'embed/forum/enable/'.Gdn::Session()->TransientKey(), 'SmallButton');
   } else {
      echo Wrap('<span style="background: #ff0;">'.T('Embedding is currently ENABLED.').'</span>', 'p');
      echo Anchor('Disable Embedding', 'embed/forum/disable/'.Gdn::Session()->TransientKey(), 'SmallButton');
      echo Wrap("Use the WordPress plugin to embed your forum into a page on your blog, or use the universal code to embed your forum into any page on the web.", 'p');
   ?>
</div>
<?php
echo $this->Form->Close();
?>
<div class="Tabs FilterTabs">
   <ul>
      <li<?php echo $this->EmbedType == 'wordpress' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('WordPress Plugin'), 'embed/forum/wordpress'); ?></li>
      <li<?php echo $this->EmbedType == 'universal' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Universal Code'), 'embed/forum/universal'); ?></li>
      <li<?php echo $this->EmbedType == 'advanced' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Advanced Settings'), 'embed/advanced'); ?></li>
   </ul>
</div>
<?php if ($this->EmbedType == 'wordpress') { ?>
   <h1><?php echo T('Ready-made Vanilla Forum Plugin for WordPress'); ?></h1>
   <div class="Info">
      <h2>Using WordPress?</h2>
      <p>To embed your forum in a page on your WordPress site, grab our ready-made plugin from WordPress.org for easy integration.</p>
   </div>
   <?php echo Anchor('Get The Vanilla Forums Plugin from WordPress.org Now', 'http://wordpress.org/extend/plugins/vanilla-forums/', 'Button'); ?>
   <div class="Info">
      <h2>Not Using WordPress?</h2>
      <p>If you are not using WordPress, you can <?php echo Anchor('use the universal code', 'embed/forum/universal'); ?> for embedding your Vanilla Forum.</p>
   </div>
<?php } else if ($this->EmbedType == 'universal') { ?>
   <style type="text/css">
.CopyBox {
    font-family: 'Inconsolata', Courier, monospace;
    font-size: 12px;
    box-shadow: inset 0 0 3px #333;
    white-space: pre;
    overflow: auto;
    padding: 8px 10px;
    background: #fffff3;
}

.CopyBox strong {
    color: #000;
    background: #ffa;
    padding: 2px 0;
}

p.WarningMessage {
    padding: 6px;
    margin-top: 0;
    border-top: 0;
}
   </style>
   
   <h1><?php echo T('Unviersal Forum Embed Code'); ?></h1>
   <div class="Info">
      <p><?php echo T('To embed your Vanilla forum into your web site, use the following code.'); ?></p>
      <div class="CopyBox">&lt;script type="text/javascript" src="<?php echo Url('js/embed.js', TRUE); ?>">&lt;/script>
&lt;noscript>Please enable JavaScript to view the &lt;a href="http://vanillaforums.com/?ref_noscript">discussions powered by Vanilla.&lt;/a>&lt;/noscript>
&lt;div class="vanilla-credit">&lt;a class="vanilla-anchor" href="http://vanillaforums.com">Discussions by &lt;span class="vanilla-logo">Vanilla&lt;/span>&lt;/a>&lt;/div>
</div>
      <p class="WarningMessage">&uarr; Copy and paste this code into the web page where you want the forum to appear.</p>
<?php 
   } 
}
?>
   </div>
