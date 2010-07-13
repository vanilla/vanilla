<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php
      echo Wrap(T('Tags are keywords that users can assign to discussions to help categorize their question with similar questions.'), 'div');
      echo Anchor(
         T(C('Plugins.Tagging.Enabled') ? 'Disable Tagging' : 'Enable Tagging'),
         'settings/toggletagging/'.Gdn::Session()->TransientKey(),
         'SmallButton'
      );
   ?>
</div>
<?php if (C('Plugins.Tagging.Enabled')) { ?>
<div class="Info">
   <h4><?php printf(T('%s tags in the system'), $this->TagData->NumRows()); ?></h4>
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
               echo Anchor($Tag->Name.' '.Wrap($Tag->CountDiscussions), 'settings/edittag/'.$Tag->TagID, 'TagName');
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