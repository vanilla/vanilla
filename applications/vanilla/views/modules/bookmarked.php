<?php if (!defined('APPLICATION')) exit();
require_once $this->FetchViewLocation('helper_functions');
require_once Gdn::Controller()->FetchViewLocation('helper_functions', 'Discussions', 'Vanilla');

$Bookmarks = $this->Data('Bookmarks');
?>
<div id="Bookmarks" class="Box BoxBookmarks">
   <h4><?php echo T('Bookmarked Discussions'); ?></h4>
   <?php if (count($Bookmarks->Result()) > 0): ?>
   
   <ul id="<?php echo $this->ListID; ?>" class="PanelInfo PanelDiscussions DataList">
      <?php
      foreach ($Bookmarks->Result() as $Discussion) {
         WriteModuleDiscussion($Discussion);
      }
      if ($Bookmarks->NumRows() == $this->Limit) {
      ?>
      <li class="ShowAll"><?php echo Anchor(T('All Bookmarks'), 'discussions/bookmarked'); ?></li>
      <?php } ?>
   </ul>
   
   <?php else: ?>
   <div class="P PagerWrapper">
      <?php
         echo sprintf(
            T('Click the %s beside discussions to bookmark them.'),
            '<a href="javascript: void(0);" class="Bookmark"> </a>');
      ?>
   </div>
   <?php endif; ?>
</div>