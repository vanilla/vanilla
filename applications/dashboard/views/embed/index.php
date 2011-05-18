<?php if (!defined('APPLICATION')) exit();
$this->EmbedType = GetValue('0', $this->RequestArgs, 'comments');
  
?>
<h1><?php echo T('&lt;Embed&gt; Vanilla'); ?></h1>
<div class="Info">
   <?php echo T('Vanilla can be embedded into your site in a variety of ways. Click the tabs below to find out more.'); ?>
</div>
<div class="Tabs FilterTabs">
   <ul>
      <li<?php echo $this->EmbedType == 'comments' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Embed Comments'), 'embed/comments'); ?></li>
      <li<?php echo $this->EmbedType == 'forum' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Embed Forum'), 'embed/forum'); ?></li>
      <li<?php echo $this->EmbedType == 'modules' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Embed Modules'), 'embed/modules'); ?></li>
   </ul>
</div>
<?php if ($this->EmbedType == 'forum') { ?>
   <h1><?php echo T('Embed your entire Vanilla forum'); ?></h1>
   <div class="Info">
      <p><?php echo T('To embed your entire Vanilla community forum into your web site, copy and paste this script into the page where you would like the forum to appear.'); ?></p>
      <pre>&lt;script type="text/javascript" src="<?php echo Asset('js/embed.js', TRUE); ?>">&lt;/script></pre>
   </div>
</div>   
   
<?php } else if ($this->EmbedType == 'modules') { ?>
   <h1><?php echo T('Embed modules from your Vanilla forum into your site'); ?></h1>
   <div class="Info"></div>
   
<?php } else { ?>
<h1><?php echo T('Use Vanilla as a commenting system in your site'); ?></h1>
<div class="Info">
   <p>You can use Vanilla as a commenting system for your website, and all
   contributed comments will also be present and indexable in your discussion
   forum. Vanilla Comments can be installed on any website using the following
   generic script.</p>
   
   <p><strong>Note:</strong> You MUST define the <code>vanilla_forum_url</code>
   and <code>vanilla_identifier</code> settings before pasting this script into
   your web page.</p>
   
   <pre>&lt;div id="vanilla-embed">&lt;/div>
&lt;script type="text/javascript">
/* Configuration Settings: Edit before pasting into your web page */
var vanilla_forum_url = 'http://pip.local/vanilla/'; // Required: the full http url & path to your vanilla forum ie. http://yourdomain.com/path/to/forum/
var vanilla_identifier = 'your-content-identifier'; // Required: a unique identifier for the web page & comments

/* Optional */
// var vanilla_url = 'http://yourdomain.com/page-with-comments.html'; // Not required: the full http url & path of the page where this script is embedded.
// var vanilla_type = 'blog'; // possibly used to render the discussion body a certain way in the forum? Also used to filter down to foreign types so that matching foreign_id's across type don't clash.
// var vanilla_name = 'Page with Comments';
// var vanilla_body = ''; // Want the forum discussion body to appear a particular way?
// var vanilla_discussion_id = ''; // In case you want to embed a particular discussion
// var vanilla_category_id = ''; // vanilla category id to force the discussion to be inserted into?

/*** DON'T EDIT BELOW THIS LINE ***/
(function() {
   var vanilla = document.createElement('script');
   vanilla.type = 'text/javascript';
   var timestamp = new Date().getTime();
   vanilla.src = vanilla_forum_url + '/js/embed.js';
   (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla);
})();
&lt;/script>
&lt;noscript>Please enable JavaScript to view the &lt;a href="http://vanillaforums.com/?ref_noscript">comments powered by Vanilla.&lt;/a>&lt;/noscript>
   </pre>
   <em>Copy and paste this script into the web page where you would like the comments to appear.</em>
</div>
<?php }