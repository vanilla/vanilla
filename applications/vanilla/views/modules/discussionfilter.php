<?php if (!defined('APPLICATION')) exit();

$Controller = Gdn::Controller();
$Session = Gdn::Session();
$Title = property_exists($Controller, 'Category') ? GetValue('Name', $Controller->Category, '') : '';
if ($Title == '')
   $Title = T('All Discussions');
      
$Bookmarked = T('My Bookmarks');
$MyDiscussions = T('My Discussions');
$MyDrafts = T('My Drafts');
$CountBookmarks = 0;
$CountDiscussions = 0;
$CountDrafts = 0;

if ($Session->IsValid()) {
   $CountBookmarks = $Session->User->CountBookmarks;
   $CountDiscussions = $Session->User->CountDiscussions;
   $CountDrafts = $Session->User->CountDrafts;
}

if (!function_exists('FilterCountString')) {
   function FilterCountString($Count, $Url = '') {
      $Count = CountString($Count, $Url);
      return $Count != '' ? '<span class="Aside">'.$Count.'</span>' : '';
   }
}
if (C('Vanilla.Discussions.ShowCounts', TRUE)) {
   $Bookmarked .= FilterCountString($CountBookmarks, Url('/discussions/UserBookmarkCount'));
   $MyDiscussions .= FilterCountString($CountDiscussions);
   $MyDrafts .= FilterCountString($CountDrafts);
}
?>
<div class="BoxFilter BoxDiscussionFilter">
   <ul class="FilterMenu">
      <?php
      $Controller->FireEvent('BeforeDiscussionFilters');     
//      if (C('Vanilla.Categories.ShowTabs')) {
      if (C('Vanilla.Categories.Use')) {
         $CssClass = 'AllCategories';
         if (strtolower($Controller->ControllerName) == 'categoriescontroller' && in_array(strtolower($Controller->RequestMethod), array('index', 'all'))) {
            $CssClass .= ' Active';
         }

         echo '<li class="'.$CssClass.'">'.Anchor(Sprite('SpAllCategories').' '.T('Categories'), '/categories').'</li> ';
      }
      ?>
      <li class="Discussions<?php echo strtolower($Controller->ControllerName) == 'discussionscontroller' && strtolower($Controller->RequestMethod) == 'index' ? ' Active' : ''; ?>"><?php echo Gdn_Theme::Link('forumroot', Sprite('SpDiscussions').' '.T('Recent Discussions')); ?></li>
      <?php echo Gdn_Theme::Link('activity', Sprite('SpActivity').' '.T('Activity'), '<li class="Activities"><a href="%url" class="%class">%text</a></li>'); ?>
      <?php if ($CountBookmarks > 0 || $Controller->RequestMethod == 'bookmarked') { ?>
      <li class="MyBookmarks<?php echo $Controller->RequestMethod == 'bookmarked' ? ' Active' : ''; ?>"><?php echo Anchor(Sprite('SpBookmarks').' '.$Bookmarked, '/discussions/bookmarked'); ?></li>
      <?php
      }
      if (($CountDiscussions > 0 || $Controller->RequestMethod == 'mine') && C('Vanilla.Discussions.ShowMineTab', TRUE)) {
      ?>
      <li class="MyDiscussions<?php echo $Controller->RequestMethod == 'mine' ? ' Active' : ''; ?>"><?php echo Anchor(Sprite('SpMyDiscussions').' '.$MyDiscussions, '/discussions/mine'); ?></li>
      <?php
      }
      if ($CountDrafts > 0 || $Controller->ControllerName == 'draftscontroller') {
      ?>
      <li class="MyDrafts<?php echo $Controller->ControllerName == 'draftscontroller' ? ' Active' : ''; ?>"><?php echo Anchor(Sprite('SpMyDrafts').' '.$MyDrafts, '/drafts'); ?></li>
      <?php
      }
      $Controller->FireEvent('AfterDiscussionFilters');
      ?>
   </ul>
</div>