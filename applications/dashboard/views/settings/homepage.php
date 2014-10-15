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
   $CssClass .= ' Choice';
   echo Anchor(T($Title).Wrap(Sprite($SpriteClass), 'span', array('class' => 'Wrap')), $Url, array('class' => $CssClass, 'title' => $Description, 'rel' => $Url));
}
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
   
   $('.HomeOptions a.Choice').click(function() {
      $('.HomeOptions a.Choice').removeClass('Current');
      $(this).addClass('Current');
      var page = $(this).attr('rel');
      $('#Form_Target').val(page);
      return false;
   });
   
   $('.LayoutOptions a.Choice').click(function() {
      var parent = $(this).parents('.LayoutOptions');
      var layoutContainer = $(parent).hasClass('DiscussionsLayout') ? 'DiscussionsLayout' : 'CategoriesLayout';
      $(parent).find('a').removeClass('Current');
      $(this).addClass('Current');
      var layout = $(this).attr('rel');
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
      $CurrentTarget = $this->Data('CurrentTarget');
      
      if (Gdn::ApplicationManager()->CheckApplication('Vanilla')) {
         echo WriteHomepageOption('Discussions', 'discussions', 'SpDiscussions', $CurrentTarget);
         echo WriteHomepageOption('Categories', 'categories', 'SpCategories', $CurrentTarget);
         // echo WriteHomepageOption('Categories &amp; Discussions', 'categories/discussions', 'categoriesdiscussions', $CurrentTarget);
      }
      //echo WriteHomepageOption('Activity', 'activity', 'SpActivity', $CurrentTarget);
      
      if (Gdn::PluginManager()->CheckPlugin('Reactions')) {
         echo WriteHomepageOption('Best Of', 'bestof', 'SpBestOf', $CurrentTarget);
      }
      ?>
   </div>
   <?php if (Gdn::ApplicationManager()->CheckApplication('Vanilla')): ?>
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
         <?php echo Wrap(T('Categories Layout'), 'strong'); ?> (<?php echo Anchor(T("adjust layout"), '/vanilla/settings/managecategories', array('class' => 'AdjustCategories')); ?>)
         <br /><?php echo T('Choose the preferred layout for the categories page.'); ?>
      </p>
      <?php
      echo WriteHomepageOption('Modern Layout', 'modern', 'SpCategories', $CurrentCategoriesLayout, T('Modern non-table-based layout'));
      echo WriteHomepageOption('Table Layout', 'table', 'SpCategoriesTable', $CurrentCategoriesLayout, T('Classic table layout used by traditional forums'));
      echo WriteHomepageOption('Mixed Layout', 'mixed', 'SpCategoriesMixed', $CurrentCategoriesLayout, T('All categories listed with a selection of 5 recent discussions under each'));
      ?>
   </div>
   <?php endif; ?>
</div>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();
echo $this->Form->Hidden('Target');
echo $this->Form->Hidden('DiscussionsLayout', array('value' => $CurrentDiscussionLayout));
echo $this->Form->Hidden('CategoriesLayout', array('value' => $CurrentCategoriesLayout));
echo $this->Form->Close('Save');
