<?php

$Category = $this->Data('Category');
if (!$Category)
   return;

$SubCategories = CategoryModel::MakeTree(CategoryModel::Categories(), $Category);

if (!$SubCategories)
   return;
   
require_once $this->FetchViewLocation('helper_functions', 'categories', 'vanilla');

?>
<h2 class="ChildCategories-Title Hidden"><?php echo T('Child Categories'); ?></h2>
<ul class="DataList ChildCategoryList">
   <?php
   foreach ($SubCategories as $Row):
      if (!$Row['PermsDiscussionsView'])
         continue;
      
      $Row['Depth'] = 1;
      ?>
      <li id="Category_<?php echo $Row['CategoryID']; ?>" class="Item Category">
         <div class="ItemContent Category">
            <h3 class="CategoryName TitleWrap"><?php 
               echo Anchor(htmlspecialchars($Row['Name']), $Row['Url'], 'Title');
               Gdn::Controller()->EventArguments['Category'] = $Row;
               Gdn::Controller()->FireEvent('AfterCategoryTitle'); 
            ?></h3>
            
            <?php if ($Row['Description']): ?>
            <div class="CategoryDescription">
               <?php echo $Row['Description']; ?>
            </div>
            <?php endif; ?>
            
            <div class="Meta Hidden">
               <span class="MItem MItem-Count DiscussionCount"><?php
                  echo Plural(
                     $Row['CountDiscussions'],
                     '%s discussion',
                     '%s discussions',
                     Gdn_Format::BigNumber($Row['CountDiscussions'], 'html'));
               ?></span>

               <span class="MItem MItem-Count CommentCount"><?php
                  echo Plural(
                     $Row['CountComments'],
                     '%s comment',
                     '%s comments',
                     Gdn_Format::BigNumber($Row['CountComments'], 'html'));
               ?></span>
            </div>
         </div>
      </li>
      <?php
   endforeach;
   ?>
</ul>