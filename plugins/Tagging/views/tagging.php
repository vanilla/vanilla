<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T('Tags are keywords that users can assign to discussions to help categorize their question with similar questions.'); ?>
</div>

<?php echo $this->Form->Open(); ?>
<div class="Wrap">
   <?php
      echo $this->Form->Errors();

      echo '<p>', T('Search for a tag.', 'Search for all or part of a tag.'), '</p>';

      echo $this->Form->TextBox('Search');
      echo ' '.$this->Form->Button(T('Go'));
      printf(T('%s tag(s) found.'), $this->Data('RecordCount'));

      echo ' '.Anchor('Add Tag', '/settings/tags/add', 'Popup Button');

   ?>
</div>
<div class="Wrap">
   <?php
   echo T('Click a tag name to edit. Click x to remove.');
   echo ' ';
   echo T("Red tags are special and can't be removed.");
   ?>
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
            $CssClass = 'TagAdmin';
            $Title = '';
            $Special = FALSE;

            if (GetValue('Type', $Tag)) {
               $Special = TRUE;
               $CssClass .= " Tag-Special Tag-{$Tag['Type']}";
               $Title = T('This is a special tag.');
            }

            ?>
            <div id="<?php echo "Tag_{$Tag['TagID']}"; ?>" class="<?php echo $CssClass;?>" title="<?php echo $Title; ?>">
               <?php
               $DisplayName = TagFullName($Tag);

               if ($Special) {
                  echo htmlspecialchars($DisplayName).' '.Wrap($Tag['CountDiscussions'], 'span', array('class' => 'Count'));
               } else {
                  echo Anchor(
                     htmlspecialchars($DisplayName).' '.Wrap($Tag['CountDiscussions'], 'span', array('class' => 'Count')),
                     "/settings/tags/edit/{$Tag['TagID']}",
                     'TagName Tag_'.str_replace(' ', '_', $Tag['Name'])
                  );

                  echo ' '.Anchor('×', "/settings/tags/delete/{$Tag['TagID']}", 'Delete Popup');
               }
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
