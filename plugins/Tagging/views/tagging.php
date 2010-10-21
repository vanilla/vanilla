<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T('Tags are keywords that users can assign to discussions to help categorize their question with similar questions.'); ?>
</div>
<div class="FilterMenu">
      <?php
      echo Anchor(
         T(C('Plugins.Tagging.Enabled') ? 'Disable Tagging' : 'Enable Tagging'),
         'settings/toggletagging/'.Gdn::Session()->TransientKey(),
         'SmallButton'
      );
   ?>
</div>
<?php if (C('Plugins.Tagging.Enabled')) { ?>
<h3><?php printf(T('%s tags in the system'), $this->TagData->NumRows()); ?></h3>
<div class="Info">
   <?php echo T('Click a tag name to edit. Click x to remove.'); ?>
</div>
<div class="Tags">
   <?php
      $Session = Gdn::Session();
      $TagCount = $this->TagData->NumRows();
      if ($TagCount == 0) {
         echo T("There are no tags in the system yet.");
      } else {
         foreach ($this->TagData->Result() as $Tag) {
            ?>
            <div class="Tag">
               <?php
               echo Anchor(htmlspecialchars($Tag->Name).' '.Wrap($Tag->CountDiscussions), 'settings/edittag/'.$Tag->TagID, 'TagName');
               echo ' '.Anchor('×', 'settings/deletetag/'.$Tag->TagID.'/'.$Session->TransientKey(), 'Delete');
               ?>
            </div>
            <?php
         }
      }
   ?>
</div>
<?php
}