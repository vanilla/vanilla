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
<?php if (C('Plugins.Tagging.Enabled')): 
echo $this->Form->Open();   
?>
<hr />
<div class="Info">
   <?php
      echo $this->Form->Errors();

      echo '<p>', T('Search for a tag.', 'Search for all or part of a tag.'), '</p>';

      echo $this->Form->TextBox('Search');
      echo ' '.$this->Form->Button(T('Go'));
      printf(T('%s tag(s) found.'), $this->Data('RecordCount'));
      
   ?>
</div>
<div class="Info">
   <?php echo T('Click a tag name to edit. Click x to remove.'); ?>
</div>
<div class="Tags">
   <?php
      $Session = Gdn::Session();
      $TagCount = $this->Data('RecordCount');
      if ($TagCount == 0) {
         echo T("There are no tags in the system yet.");
      } else {
         $Tags = $this->Data('Tags');
         foreach ($Tags as $Tag) {
            ?>
            <div class="Tag<?php echo GetValue('Type', $Tag) ? ' Tag-'.$Tag['Type'] : '' ?>">
               <?php
               echo Anchor(htmlspecialchars($Tag['Name']).' '.Wrap($Tag['CountDiscussions'], 'span', array('class' => 'Count')), 'settings/edittag/'.$Tag['TagID'], 'TagName');
               echo ' '.Anchor('×', 'settings/deletetag/'.$Tag['TagID'].'/'.$Session->TransientKey(), 'Delete');
               ?>
            </div>
            <?php
         }
      }
   ?>
</div>
<?php

PagerModule::Write();

echo $this->Form->Close();

endif;