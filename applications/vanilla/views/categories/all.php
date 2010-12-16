<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Tabs Headings CategoryHeadings">
   <div class="ItemHeading"><?php echo T('All Categories'); ?></div>
</div>
<ul class="DataList CategoryList">
<?php
   foreach ($this->CategoryData->Result() as $Category) {
      if ($Category->CategoryID > 0) {
         $LastComment = UserBuilder($Category, 'LastComment');
         
         echo '<li class="Item Depth'.$Category->Depth.'">';
         echo '<div class="ItemContent Category">';
         echo Anchor(Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode, 'Title');
         echo Wrap($Category->Description, 'div', array('class' => 'CategoryDescription'));
         ?>
            <div class="Meta">
               <span class="DiscussionCount"><?php
               printf(Plural(number_format($Category->CountDiscussions), '%s discussion', '%s discussions'), $Category->CountDiscussions);
               ?></span>
               <span class="CommentCount"><?php printf(Plural(number_format($Category->CountComments), '%s comment', '%s comments'), $Category->CountComments); ?></span>
               <?php
               if ($Category->LastCommentID != '' && $Category->LastDiscussionName != '') {
                  echo '<span class="LastDiscussionName">'.sprintf(
                     T('Most recent: %1$s by %2$s'),
                     Anchor(SliceString($Category->LastDiscussionName, 40), '/discussion/'.$Category->LastDiscussionID.'/'.Gdn_Format::Url($Category->LastDiscussionName)),
                     UserAnchor($LastComment)
                  ).'</span>';
                  echo '<span class="LastCommentDate">'.Gdn_Format::Date($Category->DateLastComment).'</span>';
               }
               ?>
            </div>
         <?php
         echo '</div>';
         echo '</li>';
      }
   }
?>
</ul>