<?php

$SubCategories = CategoryModel::MakeTree($this->Data('Category'));
$SubCategories = array_pop($SubCategories);

if (!$SubCategories || empty($SubCategories['Children']))
   return;
   
require_once $this->FetchViewLocation('helper_functions', 'categories', 'vanilla');

?>
<h2 class="ChildCategories-Title Hidden"><?php echo T('Child Categories'); ?></h2>
<ul class="DataList ChildCategoryList">
   <?php
   foreach ($SubCategories['Children'] as $Row):
      if (!$Row['PermsDiscussionsView'])
         continue;
      
      $Row['Depth'] = 1;
      ?>
      <li id="Category_<?php echo $Row['CategoryID']; ?>" class="Item Category">
         <div class="ItemContent Category">
            <?php echo Wrap(Anchor($Row['Name'], $Row['Url'], 'Title'), 'h3', array('class' => 'CategoryName TitleWrap')); ?>
            
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