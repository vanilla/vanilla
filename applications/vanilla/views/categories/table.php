<?php if (!defined('APPLICATION')) exit();
/**
 * "Table" layout for categories. Mimics more traditional forum category layout.
 */
?>

<h1 class="HomepageTitle"><?php echo $this->Data('Title'); ?></h1>
<p class="PageDescription"><?php echo $this->Description(); ?></p>

<?php
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
$TableOpen = '<table class="DataTable CategoryTable' . ($DoHeadings ? ' CategoryTableWithHeadings' : '') . '">';

//if (!$DoHeadings) {
   $TableOpen .= '<thead>
      <tr>
         <td class="CategoryName">'.T('Category').'</td>
         <td class="BigCount CountDiscussions">'.T('Discussions').'</td>
         <td class="BigCount CountComments">'.T('Comments').'</td>
         <td class="BlockColumn LatestPost">'.T('Latest Post').'</td>
      </tr>
   </thead>
   <tbody>';
//}
$TableClose = '</tbody>
</table>';

if ($DoHeadings)
   $TableClose .= '</div>'; // close out .HeadingGroup

$Alt = FALSE;
$TableIsOpen = FALSE;
foreach ($this->Data('Categories')->Result() as $Category) {
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

      if ($Category->Depth == 1) {
         if ($TableIsOpen)
            $CatList .= $TableClose;

         $TableIsOpen = TRUE;
//         $CatList .= $TableOpen;
      }

      if ($Category->Depth >= $MaxDisplayDepth && $MaxDisplayDepth > 0) {
         if ($ChildCategories != '')
            $ChildCategories .= ', ';
         $ChildCategories .= Anchor(Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode);
      } else if ($DoHeadings && $Category->Depth == 1) {
         
         $CatList .= '<div class="CategoryGroup">'.
            '<h2>'.$Category->Name.'</h2>'.
            $TableOpen;
         
//         $CatList .= '<thead>
//         <tr class="Item CategoryHeading Depth1 Category-'.$Category->UrlCode.' '.$CssClasses.'">
//            <td>'.Gdn_Format::Text($Category->Name).'</td>
//            <td>'.T('Discussions').'</td>
//            <td>'.T('Comments').'</td>
//            <td>'.T('Last Post').'</td>
//         </tr>
//         </thead>';
         $Alt = FALSE;
      } else {
         $LastComment = UserBuilder($Category, 'Last');
         $AltCss = $Alt ? ' Alt' : '';
         $Alt = !$Alt;
         $CatList .= '<tr class="Item Depth'.$Category->Depth.$AltCss.' Category-'.$Category->UrlCode.' '.$CssClasses.'">
            <td class="CategoryName">'
               .Anchor(Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode, 'Title')
               .Wrap($Category->Description.' '.Anchor(T('RSS'), $Category->Url.'/feed.rss', 'RssButton', array('title' => T('RSS feed'))), 'div', array('class' => 'CategoryDescription'));

               // If this category is one level above the max display depth, and it
               // has children, add a replacement string for them.
               if ($MaxDisplayDepth > 0 && $Category->Depth == $MaxDisplayDepth - 1 && $Category->TreeRight - $Category->TreeLeft > 1)
                  $CatList .= '{ChildCategories}';
            $CatList .= '</td>
            <td class="BigCount CountDiscussions">
               <div class="Wrap">'
                  .BigPlural($Category->CountAllDiscussions, '%s discussion')
               .'</div>
            </td>
            <td class="BigCount CountComments">
               <div class="Wrap">'
                  .BigPlural($Category->CountAllComments, '%s comment')
               .'</div>
            </td>
            <td class="BlockColumn LatestPost">
               <div class="Block Wrap">';
                  if ($LastComment && $Category->LastTitle != '') {
                     $CatList .= UserPhoto($LastComment, 'PhotoLink');
                     $CatList .= Anchor(
                        SliceString(Gdn_Format::Text($Category->LastTitle), 100),
                        $Category->LastUrl,
                        'BlockTitle LatestPostTitle'
                     );
                     $CatList .= '<div class="Meta">';
                     $CatList .= ' '.UserAnchor($LastComment, 'UserLink MItem');
                     $CatList .= ' <span class="Bullet">•</span> ';
                     $CatList .= ' '.Anchor(
                        Gdn_Format::Date($Category->LastDateInserted, 'html'),
                        $Category->LastUrl,
                        'CommentDate MItem'
                     );
                     $CatList .= '</div>';
                  } else {
                     $CatList .= '&nbsp;';
                  }
                  $CatList .= '</div>
            </td>
         </tr>';
      }
   }
}
// If there are any remaining child categories that have been collected, do
// the replacement one last time.
if ($ChildCategories != '')
   $CatList = str_replace('{ChildCategories}', '<span class="ChildCategories">'.Wrap(T('Child Categories:'), 'b').' '.$ChildCategories.'</span>', $CatList);

echo $CatList;
if ($TableIsOpen)
   echo $TableClose;
