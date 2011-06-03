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
      <li<?php echo $this->EmbedType == 'settings' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Advanced Settings'), 'embed/settings'); ?></li>
   </ul>
</div>
<?php if ($this->EmbedType == 'settings') { ?>
   <h1><?php echo T('Advanced settings for embedded community elements'); ?></h1>
   <div class="Info">
      <h2>Trusted Domains</h2>
      <p>You can optionally specify a white-list of trusted domains (ie.
      yourdomain.com) that are allowed to embed elements of your community
      (forum, comments, or modules).</p>
      <p><small>
         <strong>Notes:</strong>
         Specify one domain per line, without protocol (ie. yourdomain.com).
         <br />The domain will include all subdomains. So, yourdomain.com will
         also allow blog.yourdomain.com, news.yourdomain.com, etc.
         <br />Leaving this input blank will mean that you allow your forum to
         be embedded anywhere.
      </small></p>
      <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
      echo Wrap($this->Form->TextBox('Garden.TrustedDomains', array('MultiLine' => TRUE)), 'p');
      ?>
      <?php echo $this->Form->Close('Save', '', array('style' => 'margin: 0;')); ?>
   </div>
<?php } else if ($this->EmbedType == 'forum') { ?>
   <h1><?php echo T('Embed your entire Vanilla forum'); ?></h1>
   <div class="Info">
      <p><?php echo T('To embed your entire Vanilla community forum into your web site, copy and paste this script into the page where you would like the forum to appear.'); ?></p>
      <pre>&lt;div id="vanilla-comments">&lt;/div>
&lt;script type="text/javascript">
/* Configuration Settings: Edit before pasting into your web page */
var vanilla_forum_url = 'http://yourdomain.com/path/to/forum/'; // Required: the full http url & path to your vanilla forum

/*** DON'T EDIT BELOW THIS LINE ***/
(function() {
   var vanilla = document.createElement('script');
   vanilla.type = 'text/javascript';
   var timestamp = new Date().getTime();
   vanilla.src = vanilla_forum_url + '/js/embed.js';
   (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla);
})();
&lt;/script>
&lt;noscript>Please enable JavaScript to view the &lt;a href="http://vanillaforums.com/?ref_noscript">discussions powered by Vanilla.&lt;/a>&lt;/noscript>
&lt;div class="vanilla-credit">&lt;a class="vanilla-anchor" href="http://vanillaforums.com">Discussions by &lt;span class="vanilla-logo">Vanilla&lt;/span>&lt;/a>&lt;/div>
</pre>
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
   
   <pre>&lt;div id="vanilla-comments">&lt;/div>
&lt;script type="text/javascript">
/* Configuration Settings: Edit before pasting into your web page */
var vanilla_forum_url = 'http://yourdomain.com/path/to/forum/'; // Required: the full http url & path to your vanilla forum
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
&lt;div class="vanilla-credit">&lt;a class="vanilla-anchor" href="http://vanillaforums.com">Comments by &lt;span class="vanilla-logo">Vanilla&lt;/span>&lt;/a>&lt;/div>
   </pre>
   <p><em>Copy and paste this script into the web page where you would like the comments to appear.</em></p>
   <p>&nbsp;</p>
   <h2>Comment Counts</h2>
   <p>If you want to display the number of comments in each page on an index page, you can use the following simple javascript code snippet.</p>
   <p>Include the following code on the page that displays the comment counts, most likely the main page of your blog or website. The code should be pasted at the bottom of the webpage right before the closing &lt;/body> tag.</p>

   <pre>&lt;script type="text/javascript">
var vanilla_forum_url = 'http://yourdomain.com/path/to/forum/'; // Required: the full http url & path to your vanilla forum
/*** DON'T EDIT BELOW THIS LINE ***/
(function() {
   var timestamp = new Date().getTime();
   var vanilla_count = document.createElement('script');
   vanilla_count.type = 'text/javascript';
   vanilla_count.src = vanilla_forum_url + '/js/count.js?time='+timestamp;
   (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla_count);
})();
&lt;/script>
</pre>
   <p>Next you need to tell Vanilla where to place the comment counts, and what count to use. To achieve this, simply add a <strong>vanilla-identifier</strong> attribute to the anchor linking to the comments in question. The vanilla-identifier is the same value used above when embedding the comments into the page.</p>
   <pre>&lt;a href="http://yourdomain.com/path/to/page/with/comments/#vanilla_comments" vanilla-identifier="embed-test">Comments&lt;/a></pre>
   
   <p>Vanilla will then replace the content of the anchor (in this case, the word "Comments") with the number of comments on the page in question.</p>
</div>
<?php }