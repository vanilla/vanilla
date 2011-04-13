<?php if (!defined('APPLICATION')) exit();
$ShowAllCategoriesPref = Gdn::Session()->GetPreference('ShowAllCategories');
$Url = Gdn::Request()->Path();
?>
<div class="Box CategoryFollowToggleBox">
   <h4><?php echo T('Category Management'); ?></h4>
   <?php if ($ShowAllCategoriesPref) { ?>
      <p>You are currently viewing all categories.<p>
   <?php
      echo Wrap(Anchor('Only show followed categories', $Url.'?ShowAllCategories=false'), 'p');
   } else {
   ?>
      <p>You are currently only viewing categories that you follow.</p>
   <?php
      echo Wrap(Anchor('Show unfollowed categories', $Url.'?ShowAllCategories=true'), 'p');
   }
   ?>
</div>