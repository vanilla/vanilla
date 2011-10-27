<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
function WriteHomepageOption($Title, $Url, $CssClass, $Current) {
   if ($Current == $Url)
      $CssClass .= ' Current';
   echo Anchor(T($Title).'<span></span>', $Url, array('class' => $CssClass));
}
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
   $('.HomeOptions a').click(function() {
      $('.HomeOptions a').removeClass('Current');
      $(this).addClass('Current');
      var route = this.className.replace(' Current', '');
      if (route == 'categoriesdiscussions')
         route = 'categories/discussions';
      else if (route == 'categoriesall')
         route = 'categories/all';

      $('#Form_Target').val(route);
      return false;
   });
});
</script>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo '<li>', Anchor(T("Configuring Vanilla's Homepage"), 'http://vanillaforums.org/docs/homepage'), '</li>';
   echo '<li>', sprintf(T('Change the look of All Categories', 'You can change the look of the <b>All Categories</b> page <a href="%s">here</a>.'), Url('/vanilla/settings/managecategories')), '</li>';
   echo '<li>', Anchor(T('Changing the Discussions Menu Link'), 'http://vanillaforums.org/docs/homepage#discussionslink'), '</li>';
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Homepage'); ?></h1>
<div class="Info">
   <?php printf(T('Use the content at this url as your homepage.', 'Choose the page people should see when they visit: <strong style="white-space: nowrap;">%s</strong>'), Url('/', TRUE)) ?>
</div>
<div class="HomeOptions">
   <?php
   // Only show the vanilla pages if Vanilla is enabled
   $ApplicationManager = new Gdn_ApplicationManager();
   $EnabledApplications = $ApplicationManager->EnabledVisibleApplications();
   $CurrentTarget = $this->Data('CurrentTarget');
   if (array_key_exists('Vanilla', $EnabledApplications)) {
      echo WriteHomepageOption('All Discussions', 'discussions', 'discussions', $CurrentTarget);
      echo WriteHomepageOption('All Categories', 'categories/all', 'categoriesall', $CurrentTarget);
      echo WriteHomepageOption('Categories &amp; Discussions', 'categories/discussions', 'categoriesdiscussions', $CurrentTarget);
   }
   echo WriteHomepageOption('Activity', 'activity', 'activity', $CurrentTarget);
   ?>
</div>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
echo $this->Form->Hidden('Target');
echo $this->Form->Close('Save');
