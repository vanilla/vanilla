<?php if (!defined('APPLICATION')) exit();
include($this->FetchViewLocation('helper_functions', 'categories'));

$CatList = '';
$DoHeadings = C('Vanilla.Categories.DoHeadings');
$MaxDisplayDepth = C('Vanilla.Categories.MaxDisplayDepth');
$ChildCategories = '';
$this->EventArguments['NumRows'] = $this->CategoryData->NumRows();

if (C('Vanilla.Categories.ShowTabs')) {
   $ViewLocation = Gdn::Controller()->FetchViewLocation('helper_functions', 'Discussions', 'vanilla');
   include_once $ViewLocation;
   WriteFilterTabs($this);
} else {
   ?>
   <div class="Tabs Headings CategoryHeadings">
      <div class="ItemHeading"><?php echo T('All Categories'); ?></div>
   </div>
   <?php
}
echo '<ul class="DataList CategoryList'.($DoHeadings ? ' CategoryListWithHeadings' : '').'">';
   $Alt = FALSE;
   foreach ($this->CategoryData->Result() as $Category) {
      $this->EventArguments['CatList'] = &$CatList;
      $this->EventArguments['ChildCategories'] = &$ChildCategories;
      $this->EventArguments['Category'] = &$Category;
      $this->FireEvent('BeforeCategoryItem');
      $CssClasses = array(GetValue('Read', $Category) ? 'Read' : 'Unread');
      if (GetValue('Archive', $Category))
         $CssClasses[] = 'Archive';
      if (GetValue('Unfollow', $Category))
         $CssClasses[] = 'Unfollow';
      $CssClasses = implode(' ', $CssClasses);

      if ($Category->CategoryID > 0) {
         // If we are below the max depth, and there are some child categories
         // in the $ChildCategories variable, do the replacement.
         if ($Category->Depth < $MaxDisplayDepth && $ChildCategories != '') {
            $CatList = str_replace('{ChildCategories}', '<span class="ChildCategories">'.Wrap(T('Child Categories:'), 'b').' '.$ChildCategories.'</span>', $CatList);
            $ChildCategories = '';
         }

         if ($Category->Depth >= $MaxDisplayDepth && $MaxDisplayDepth > 0) {
            if ($ChildCategories != '')
               $ChildCategories .= ', ';
            $ChildCategories .= Anchor(Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode);
         } else if ($DoHeadings && $Category->Depth == 1) {
            $CatList .= '<li class="Item CategoryHeading Depth1 Category-'.$Category->UrlCode.' '.$CssClasses.'">
               <div class="ItemContent Category">'.Gdn_Format::Text($Category->Name).'</div>'
               .GetOptions($Category, $this).'
            </li>';
            $Alt = FALSE;
         } else {
            $LastComment = UserBuilder($Category, 'LastComment');
            $AltCss = $Alt ? ' Alt' : '';
            $Alt = !$Alt;
            $CatList .= '<li class="Item Depth'.$Category->Depth.$AltCss.' Category-'.$Category->UrlCode.' '.$CssClasses.'">
               <div class="ItemContent Category '.$CssClasses.'">'
                  .Anchor(Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode, 'Title')
                  .GetOptions($Category, $this)
                  .Wrap($Category->Description, 'div', array('class' => 'CategoryDescription'))
                  .'<div class="Meta">
                     <span class="RSS">'.Anchor(Img('applications/dashboard/design/images/rss.gif'), '/categories/'.$Category->UrlCode.'/feed.rss').'</span>
                     <span class="DiscussionCount">'.sprintf(Plural(number_format($Category->CountAllDiscussions), '%s discussion', '%s discussions'), $Category->CountDiscussions).'</span>
                     <span class="CommentCount">'.sprintf(Plural(number_format($Category->CountAllComments), '%s comment', '%s comments'), $Category->CountComments).'</span>';
                     if ($Category->LastCommentID != '' && $Category->LastDiscussionTitle != '') {
                        $CatList .= '<span class="LastDiscussionTitle">'.sprintf(
                              T('Most recent: %1$s by %2$s'),
                              Anchor(SliceString($Category->LastDiscussionTitle, 40), '/discussion/'.$Category->LastDiscussionID.'/'.Gdn_Format::Url($Category->LastDiscussionTitle)),
                              UserAnchor($LastComment)
                           ).'</span>'
                           .'<span class="LastCommentDate">'.Gdn_Format::Date($Category->DateLastComment).'</span>';
                     }
                     // If this category is one level above the max display depth, and it
                     // has children, add a replacement string for them.
                     if ($MaxDisplayDepth > 0 && $Category->Depth == $MaxDisplayDepth - 1 && $Category->TreeRight - $Category->TreeLeft > 1)
                        $CatList .= '{ChildCategories}';
         
                  $CatList .= '</div>
               </div>
            </li>';
         }
      }
   }
   // If there are any remaining child categories that have been collected, do
   // the replacement one last time.
   if ($ChildCategories != '')
      $CatList = str_replace('{ChildCategories}', '<span class="ChildCategories">'.Wrap(T('Child Categories:'), 'b').' '.$ChildCategories.'</span>', $CatList);
   
   echo $CatList;
?>
</ul>