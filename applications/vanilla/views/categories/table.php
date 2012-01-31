<?php if (!defined('APPLICATION')) exit();
/**
 * "Table" layout for categories. Mimics more traditional forum category layout.
 */

include($this->FetchViewLocation('helper_functions', 'categories'));

echo '<h1 class="HomepageTitle">'.$this->Data('Title').'</h1>';

$CatList = '';
$DoHeadings = C('Vanilla.Categories.DoHeadings');
$MaxDisplayDepth = C('Vanilla.Categories.MaxDisplayDepth');
$ChildCategories = '';
$this->EventArguments['NumRows'] = $this->CategoryData->NumRows();

/*
if (C('Vanilla.Categories.ShowTabs')) {
   $ViewLocation = Gdn::Controller()->FetchViewLocation('helper_functions', 'Discussions', 'vanilla');
   include_once $ViewLocation;
   WriteFilterTabs($this);
}
*/
?>
<table class="CategoryTable<?php echo $DoHeadings ? ' CategoryTableWithHeadings' : ''; ?>">
   <thead>
      <tr>
         <td class="CategoryName"><?php echo T('Category'); ?></td>
         <td class="LatestComment"><?php echo T('Latest Comment'); ?></td>
         <td class="BigCount CountDiscussions"><?php echo T('Discussions'); ?></td>
         <td class="BigCount CountComments"><?php echo T('Comments'); ?></td>
         <td class="Opts"></td>
      </tr>
   </thead>
   <tbody>
<?php
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
            $CatList .= '<tr class="Item CategoryHeading Depth1 Category-'.$Category->UrlCode.' '.$CssClasses.'">
               <td colspan="5">'
                  // .GetOptions($Category, $this)
                  .Gdn_Format::Text($Category->Name)
               .'</td>
            </tr>';
            $Alt = FALSE;
         } else {
            $LastComment = UserBuilder($Category, 'Last');
            $AltCss = $Alt ? ' Alt' : '';
            $Alt = !$Alt;
            $CatList .= '<tr class="Item Depth'.$Category->Depth.$AltCss.' Category-'.$Category->UrlCode.' '.$CssClasses.'">
               <td class="CategoryName">'
                  .Anchor(Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode, 'Title')
                  .Wrap($Category->Description, 'div', array('class' => 'CategoryDescription'));

                  // If this category is one level above the max display depth, and it
                  // has children, add a replacement string for them.
                  if ($MaxDisplayDepth > 0 && $Category->Depth == $MaxDisplayDepth - 1 && $Category->TreeRight - $Category->TreeLeft > 1)
                     $CatList .= '{ChildCategories}';
               $CatList .= '</td>
               <td class="LatestPost">
                  <div class="Wrap">';
                     if ($LastComment && $Category->LastTitle != '') {
                        $CatList .= UserPhoto($LastComment, 'PhotoLink');
                        $CatList .= Anchor(
                           SliceString(Gdn_Format::Text($Category->LastTitle), 100),
                           $Category->LastUrl,
                           'LatestPostTitle'
                        );
                        $CatList .= '<div class="Meta">';
                        $CatList .= UserAnchor($LastComment, 'UserLink');
                        $CatList .= Anchor(
                           Gdn_Format::Date($Category->LastDateInserted),
                           $Category->LastUrl,
                           'CommentDate'
                        );
                        $CatList .= '</div>';
                     } else {
                        $CatList .= '&nbsp;';
                     }
                     $CatList .= '</div>
               </td>
               <td class="BigCount CountDiscussions">
                  <div class="Wrap">'
                     .Gdn_Format::BigNumber($Category->CountAllDiscussions)
                  .'</div>
               </td>
               <td class="BigCount CountComments">
                  <div class="Wrap">'
                     .Gdn_Format::BigNumber($Category->CountAllComments)
                  .'</div>
               </td>
               <td class="Opts">'.GetOptions($Category, $this).'</td>
            </tr>';
         }
      }
   }
   // If there are any remaining child categories that have been collected, do
   // the replacement one last time.
   if ($ChildCategories != '')
      $CatList = str_replace('{ChildCategories}', '<span class="ChildCategories">'.Wrap(T('Child Categories:'), 'b').' '.$ChildCategories.'</span>', $CatList);
   
   echo $CatList;
?>
</table>