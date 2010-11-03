<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::Session();
$RecommendedThemeName = 'Embed-Friendly';
?>
<h1><?php echo T('&lt;Embed&gt; Vanilla'); ?></h1>
<div class="Info">
   <?php echo T('To embed your Vanilla community forum into a remote web application, use the forum embed code or one of the forum embed plugins below.'); ?>
</div>
<?php echo $this->Form->Open(); ?>
<div class="Embeds">
   <div class="EmbedCode">
      <strong>Forum &lt;Embed&gt; Code</strong>
      <textarea id="EmbedCode"><script type="text/javascript" src="<?php echo Asset('plugins/embedvanilla/remote.js', TRUE); ?>"></script></textarea>
      <em>Copy and paste this forum embed code into the remote application where you would like the forum to appear.</em>
      <script type="text/javascript">
      window.onload = function() {
         var TextBoxID = 'EmbedCode';
         document.getElementById(TextBoxID).focus();
         document.getElementById(TextBoxID).select();
      }
      </script>
   </div><div class="EmbedPlugins">
      <strong>Forum Embed Plugins</strong>
      <em>Use these custom-built plugins to embed the forum into various other applications without the need to touch any code.</em>
      <ul>
         <li><?php echo Anchor(T('WordPress Plugin'), 'plugins/embedvanilla/plugins/wordpress.zip', 'WordPress'); ?></li>
         <li><?php echo Anchor(T('Blogger Gadget'), 'plugin/gadgetinfo', 'Popup'); ?></li>
      </ul>
   </div>
</div>
<div class="Info">
   <?php echo T('Make sure to use a forum theme that meshes well with the look and feel of the remote site.'); ?>
</div>
<div class="Embeds">
   <div class="EmbedTheme">
      <strong>Current Theme</strong>
      <?php
      $Author = $this->Data('EnabledTheme.Author');
      $AuthorUrl = $this->Data('EnabledTheme.AuthorUrl');
      $PreviewImage = SafeGlob(PATH_THEMES . DS . $this->Data('EnabledThemeFolder') . DS . "screenshot.*");
      $PreviewImage = count($PreviewImage) > 0 ? basename($PreviewImage[0]) : FALSE;
      if ($PreviewImage && in_array(strtolower(pathinfo($PreviewImage, PATHINFO_EXTENSION)), array('gif','jpg','png')))
         echo Img('/themes/'.$this->Data('EnabledThemeFolder').'/'.$PreviewImage, array('alt' => $this->Data('EnabledThemeName'), 'height' => '112', 'width' => '150'));
      ?>
      <em>You are currently using the <?php echo Wrap($this->Data('EnabledThemeName'), 'b'); ?> theme by <?php echo $AuthorUrl != '' ? Anchor($Author, $AuthorUrl) : $Author; ?>.</em>
      <em><?php
         echo Anchor('Browse all installed themes', 'settings/themes');
         echo ', or ';
         echo Anchor('find more themes at VanillaForums.org.', 'http://vanillaforums.org/addons');
      ?></em>
   </div><?php
   $this->FireEvent('BeforeEmbedRecommend');
   // Has the user applied the recommended theme?
   if ($this->Data('EnabledThemeName') != $RecommendedThemeName) {
   ?><div class="EmbedRecommend">
      <strong>We Recommend</strong>
      <?php
      // Does the user have the recommended theme?
      foreach ($this->Data('AvailableThemes') as $Theme) {
         if (GetValue('Name', $Theme) == $RecommendedThemeName) {
            $RecommendedThemeFolder = GetValue('Folder', $Theme);
            $HasRecommendedTheme = TRUE;
         }
      }
      $PreviewImage = SafeGlob(PATH_THEMES . DS . $RecommendedThemeFolder . DS . "screenshot.*");
      $PreviewImage = count($PreviewImage) > 0 ? basename($PreviewImage[0]) : FALSE;
      if ($PreviewImage && in_array(strtolower(pathinfo($PreviewImage, PATHINFO_EXTENSION)), array('gif','jpg','png')))
         echo Img('/themes/'.$RecommendedThemeFolder.'/'.$PreviewImage, array('alt' => $RecommendedThemeName, 'height' => '112', 'width' => '150'));
         
      ?>
      <em>We recommend the <?php echo Wrap($RecommendedThemeName, 'b'); ?> theme. <?php
      if ($HasRecommendedTheme)
         echo Anchor(T('Click here to apply it.'), 'plugin/embed/'.$RecommendedThemeFolder.'/'.$Session->TransientKey());
      else
         echo Anchor(T('Click here to get it.'), 'http://vanillaforums.org/addons');
      
      ?></em>
   </div>
   <?php } ?>
</div>
<?php
if (C('Plugins.EmbedVanilla.RemoteUrl')) {
   echo Wrap(T('The "Remote Url" is the web address of the place where your embedded forum should be viewed from.'), 'div', array('class' => 'Info'));

echo $this->Form->Errors();
?>
<ul class="RemoteSettings">
   <li>
      <?php
      echo Wrap(T('Dashboard Embed'), 'strong');
      echo $this->Form->CheckBox('Plugins.EmbedVanilla.EmbedDashboard', "Don't embed your forum admin dashboard (pop it out to full-screen)");
      ?>
   </li>
   <li>
      <?php
      echo Wrap(T('Remote Url'), 'strong');
      echo $this->Form->CheckBox('Plugins.EmbedVanilla.ForceRemoteUrl', "Force your forum to be viewed through the Remote Url");
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Remote Url to Forum', 'Plugins.EmbedVanilla.RemoteUrl');
         echo $this->Form->TextBox('Plugins.EmbedVanilla.RemoteUrl');
         echo ' '.Anchor('View Page', C('Plugins.EmbedVanilla.RemoteUrl'), 'SmallButton', array('target' => '_blank'));
      ?><span>For SEO purposes, search engine crawlers are excluded from being forced to view the forum in the remote url.</span>
   </li>
   <li>
      <?php echo $this->Form->Button('Save Changes'); ?>
   </li>
</ul>
<?php
}
echo $this->Form->Close();
