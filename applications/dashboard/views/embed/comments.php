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
<h1><?php echo T('Blog Comments'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info">
   <?php
   echo T('AboutCommentEmbedding', "Vanilla can be used as a drop-in replacement for your blog's native commenting system. As a matter of fact, it can be used to add comments to any page on the web.");
   if (!$AllowEmbed) {
      echo T('Enable embedding to use blog comments.', 'In order for this to work, you will need to enable embedding.');
      echo Wrap('<span style="background: #ff0;">'.T('Embedding is currently DISABLED.').'</span>', 'p');
      echo Anchor('Enable Embedding', 'embed/comments/enable/'.Gdn::Session()->TransientKey(), 'SmallButton');
   } else {
      echo Wrap('<span style="background: #ff0;">'.T('Embedding is currently ENABLED.').'</span>', 'p');
      echo Anchor('Disable Embedding', 'embed/comments/disable/'.Gdn::Session()->TransientKey(), 'SmallButton');
      echo Wrap(T("Use the plugin for WordPress or our universal code for any other platform", "Use the WordPress plugin to set up Vanilla Comments on your blog, or use the universal code to set up Vanilla Comments on any other platform."), 'p');
   ?>
</div>
<?php
echo $this->Form->Close();
?>
<div class="Tabs FilterTabs">
   <ul>
      <li<?php echo $this->EmbedType == 'wordpress' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('WordPress Plugin'), 'embed/comments/wordpress'); ?></li>
      <li<?php echo $this->EmbedType == 'universal' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Universal Code'), 'embed/comments/universal'); ?></li>
      <li<?php echo $this->EmbedType == 'settings' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Comment Settings'), 'embed/comments/settings'); ?></li>
      <li<?php echo $this->EmbedType == 'advanced' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Advanced Settings'), 'embed/advanced'); ?></li>
   </ul>
</div>
<?php if ($this->EmbedType == 'wordpress') { ?>
   <h1><?php echo T('Ready-made Vanilla Comments Plugin for WordPress'); ?></h1>
   <div class="Info">
      <h2>Using WordPress?</h2>
      <p>If you want to use Vanilla Comments instead of your native WordPress comments, grab our ready-made plugin from WordPress.org for easy integration.</p>
   </div>
   <?php echo Anchor('Get The Vanilla Forums Plugin from WordPress.org Now', 'http://wordpress.org/extend/plugins/vanilla-forums/', 'Button'); ?>
   <div class="Info">
      <h2>Not Using WordPress?</h2>
      <p>If you are not using WordPress, you can <?php echo Anchor('use the universal code', 'embed/comments/universal'); ?> for embedding Vanilla Comments.</p>
   </div>
<?php } else if ($this->EmbedType == 'settings') { ?>
   <style type="text/css">
.WarningMessage {
    padding: 8px 10px;
    max-width: 500px;
}
   </style>
   <h1><?php echo T('Comment Settings'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         $Options = array('10' => '10', '15' => '15', '20' => '20', '25' => '25', '30' => '30', '40' => '40', '50' => '50', '100' => '100');
         $Fields = array('TextField' => 'Code', 'ValueField' => 'Code');
         echo $this->Form->Label('Comments per Page', 'Garden.Embed.CommentsPerPage');
         echo $this->Form->DropDown('Garden.Embed.CommentsPerPage', $Options, $Fields);
      ?>
   </li>
   <li>
      <?php
         $Options = array('desc' => 'Most recent first / comment form at top of list', 'asc' => 'Most recent last / comment form at bottom of list');
         $Fields = array('TextField' => 'Text', 'ValueField' => 'Code');
         echo $this->Form->Label('Sort blog comments in the following order:', 'Garden.Embed.SortComments');
         echo $this->Form->DropDown('Garden.Embed.SortComments', $Options, $Fields);
      ?>
   </li>
   <li>
      <p class="WarningMessage">
         <?php
            echo $this->Form->CheckBox('Garden.Embed.PageToForum', "Send users to forum after the first page of comments.");
         ?>
         <strong>Recommended:</strong> When there is more than one page of comments on a blog post, send users to the forum when they click to see another page of comments. This is a great way of driving users into your community.
      </p>
   </li>
</ul>
<?php 
echo $this->Form->Close('Save');
      
} else { 
?>
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

p.AlertMessage {
    padding: 6px;
    margin-bottom: 0;
    border-bottom: 0;
}      
   </style>
<h1><?php echo T('Use Vanilla as a commenting system in your site'); ?></h1>
<div class="Info">
   <p>You can use Vanilla as a commenting system for your website, and all
   contributed comments will also be present in your discussion forum. Vanilla 
   Comments can be used on any website using the following code.</p>
   
   <p class="AlertMessage"><strong>Note:</strong> You MUST define the <code>vanilla_forum_url</code>
   and <code>vanilla_identifier</code> settings before pasting this script into
   your web page.</p>
   
   <div class="CopyBox">&lt;div id="vanilla-comments">&lt;/div>
&lt;script type="text/javascript">
<strong>/*** Required Settings: Edit BEFORE pasting into your web page ***/
var vanilla_forum_url = '<?php echo Url('/', TRUE); ?>'; // The full http url & path to your vanilla forum
var vanilla_identifier = 'your-content-identifier'; // Your unique identifier for the content being commented on</strong>

/*** Optional Settings: Ignore if you like ***/
// var vanilla_discussion_id = ''; // Attach this page of comments to a specific Vanilla DiscussionID.
// var vanilla_category_id = ''; // Create this discussion in a specific Vanilla CategoryID.

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
</div>
   <p class="WarningMessage">&uarr; Copy and paste this code into the web page where you want the comments to appear.</p>
   <p>&nbsp;</p>
   <h2>Comment Counts</h2>
   <p>To show the number of comments on each blog post on your main blog page, use the following code.</p>

   <p class="AlertMessage"><strong>Note:</strong> You MUST define the <code>vanilla_forum_url</code> 
      before pasting this script into your web page.</p>

   <div class="CopyBox">&lt;script type="text/javascript">
<strong>/*** Required Settings: Edit BEFORE pasting into your web page ***/
var vanilla_forum_url = '<?php echo Url('/', TRUE); ?>'; // The full http url & path to your vanilla forum</strong>

/*** Optional Settings: customize the format of the comment counts. Html is allowed. */
// var vanilla_comments_none = 'No Comments';
// var vanilla_comments_singular = '1 Comment';
// var vanilla_comments_plural = '[num] Comments';

/*** DON'T EDIT BELOW THIS LINE ***/
(function() {
   var vanilla_count = document.createElement('script');
   vanilla_count.type = 'text/javascript';
   vanilla_count.src = vanilla_forum_url + '/js/count.js';
   (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla_count);
})();
&lt;/script>
</div>
   <p class="WarningMessage">&uarr; Copy &amp; paste this code at the bottom of the page right before the closing &lt;/body> tag.</p>
   <p>&nbsp;</p>
   <p><strong>One more thing!</strong></p>
   <p>You need to tell Vanilla where the comment counts are located in your page. To achieve this, add a <strong>vanilla-identifier</strong> attribute to the anchor linking to the comments. The vanilla-identifier is the same value used above when embedding the comments into the page.</p>
   <div class="CopyBox">&lt;a href="http://yourdomain.com/path/to/page/with/comments/#vanilla_comments" <strong>vanilla-identifier="embed-test"</strong>>Comments&lt;/a></div>
   
   <p>Vanilla will then replace the content of the anchor (in this case, the word "Comments") with the number of comments on the page in question.</p>
</div>
<?php
   }
}
?>
</div>