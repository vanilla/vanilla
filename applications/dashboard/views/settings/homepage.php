<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$CurrentDiscussionLayout = C('Vanilla.Discussions.Layout', '');
if ($CurrentDiscussionLayout == '')
   $CurrentDiscussionLayout = 'modern';
$CurrentCategoriesLayout = C('Vanilla.Categories.Layout', 'modern');
if ($CurrentCategoriesLayout == '')
   $CurrentCategoriesLayout = 'modern';

function WriteHomepageOption($Title, $Url, $CssClass, $Current, $Description = '') {
   $SpriteClass = $CssClass;
   if ($Current == $Url)
      $CssClass .= ' Current';
   echo Anchor(T($Title).Wrap(Sprite($SpriteClass), 'span', array('class' => 'Wrap')), $Url, array('class' => $CssClass, 'title' => $Description));
}
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
   $('.HomeOptions a').click(function() {
      $('.HomeOptions a').removeClass('Current');
      $(this).addClass('Current');
      var page = this.className.replace(' Current', '').replace('Sp', '').toLowerCase();
      $('#Form_Target').val(page);
      return false;
   });
   $('.LayoutOptions a').click(function() {
      var parent = $(this).parents('.LayoutOptions');
      var layoutContainer = $(parent).hasClass('DiscussionsLayout') ? 'DiscussionsLayout' : 'CategoriesLayout';
      $(parent).find('a').removeClass('Current');
      $(this).addClass('Current');
      var layout = this.className.replace(' Current', '').replace('SpDiscussions', '').replace('SpCategories', '').toLowerCase();
      $('#Form_'+layoutContainer).val(layout);
      return false;
   });
});
</script>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo Wrap(Anchor(T("Configuring Vanilla's Homepage"), 'http://vanillaforums.org/docs/homepage'), 'li');
   echo Wrap(sprintf(T('Change the look of All Categories', 'You can change the look of the <b>All Categories</b> page <a href="%s">here</a>.'), Url('/vanilla/settings/managecategories')), 'li');
   echo Wrap(Anchor(T('Changing the Discussions Menu Link'), 'http://vanillaforums.org/docs/homepage#discussionslink'), 'li');
   echo Wrap(Anchor(T("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Homepage'); ?></h1>
<div class="Info">
   <?php printf(T('Use the content at this url as your homepage.', 'Choose the page people should see when they visit: <strong style="white-space: nowrap;">%s</strong>'), Url('/', TRUE)) ?>
</div>
<div class="Homepage">
   <div class="HomeOptions">
      <?php
      // Only show the vanilla pages if Vanilla is enabled
      $ApplicationManager = new Gdn_ApplicationManager();
      $EnabledApplications = $ApplicationManager->EnabledVisibleApplications();
      $CurrentTarget = $this->Data('CurrentTarget');
      if (array_key_exists('Vanilla', $EnabledApplications)) {
         echo WriteHomepageOption('Discussions', 'discussions', 'SpDiscussions', $CurrentTarget);
         echo WriteHomepageOption('Categories', 'categories', 'SpCategories', $CurrentTarget);
         // echo WriteHomepageOption('Categories &amp; Discussions', 'categories/discussions', 'categoriesdiscussions', $CurrentTarget);
      }
      echo WriteHomepageOption('Activity', 'activity', 'SpActivity', $CurrentTarget);
      ?>
   </div>
   <?php if (array_key_exists('Vanilla', $EnabledApplications)): ?>
   <div class="LayoutOptions DiscussionsLayout">
      <p>
         <?php echo Wrap(T('Discussions Layout'), 'strong'); ?>
         <br /><?php echo T('Choose the preferred layout for the discussions page.'); ?>
      </p>
      <?php
      echo WriteHomepageOption('Modern Layout', 'modern', 'SpDiscussions', $CurrentDiscussionLayout, T('Modern non-table-based layout'));
      echo WriteHomepageOption('Table Layout', 'table', 'SpDiscussionsTable', $CurrentDiscussionLayout, T('Classic table layout used by traditional forums'));
      ?>
   </div>
   <div class="LayoutOptions CategoriesLayout">
      <p>
         <?php echo Wrap(T('Categories Layout'), 'strong'); ?>
         <br /><?php echo T('Choose the preferred layout for the categories page.'); ?>
      </p>
      <?php
      echo WriteHomepageOption('Modern Layout', 'modern', 'SpCategories', $CurrentCategoriesLayout, T('Modern non-table-based layout'));
      echo WriteHomepageOption('Table Layout', 'table', 'SpCategoriesTable', $CurrentCategoriesLayout, T('Classic table layout used by traditional forums'));
      echo WriteHomepageOption('Mixed Layout', 'mixed', 'SpCategoriesMixed', $CurrentCategoriesLayout, T('All categories listed with a selection of 5 recent discussions under each'));
      ?>
   </div>
</div>
<?php
endif;

echo $this->Form->Open();
echo $this->Form->Errors();
echo $this->Form->Hidden('Target');
echo $this->Form->Hidden('DiscussionsLayout', array('value' => $CurrentDiscussionLayout));
echo $this->Form->Hidden('CategoriesLayout', array('value' => $CurrentCategoriesLayout));
echo $this->Form->Close('Save');
