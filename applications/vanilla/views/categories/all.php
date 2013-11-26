<?php if (!defined('APPLICATION')) exit();

if (!function_exists('GetOptions'))
   include $this->FetchViewLocation('helper_functions', 'categories');
   
echo '<h1 class="H HomepageTitle">'.$this->Data('Title').'</h1>';
if ($Description = $this->Description()) {
   echo Wrap($Description, 'div', array('class' => 'P PageDescription'));
}

$CatList = '';
$DoHeadings = C('Vanilla.Categories.DoHeadings');
$MaxDisplayDepth = C('Vanilla.Categories.MaxDisplayDepth');
$ChildCategories = '';
$this->EventArguments['NumRows'] = count($this->Data('Categories'));

//if (C('Vanilla.Categories.ShowTabs')) {
////   $ViewLocation = Gdn::Controller()->FetchViewLocation('helper_functions', 'Discussions', 'vanilla');
////   include_once $ViewLocation;
////   WriteFilterTabs($this);
//   echo Gdn_Theme::Module('DiscussionFilterModule');
//}

echo '<ul class="DataList CategoryList'.($DoHeadings ? ' CategoryListWithHeadings' : '').'">';
   $Alt = FALSE;
   foreach ($this->Data('Categories') as $CategoryRow) {
      $Category = (object)$CategoryRow;
      
      $this->EventArguments['CatList'] = &$CatList;
      $this->EventArguments['ChildCategories'] = &$ChildCategories;
      $this->EventArguments['Category'] = &$Category;
      $this->FireEvent('BeforeCategoryItem');
      $CssClass = CssClass($CategoryRow);
      
      $CategoryID = GetValue('CategoryID', $Category);

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
            $ChildCategories .= Anchor(Gdn_Format::Text($Category->Name), CategoryUrl($Category));
         } else if ($DoHeadings && $Category->Depth == 1) {
            $CatList .= '<li id="Category_'.$CategoryID.'" class="CategoryHeading '.$CssClass.'">
               <div class="ItemContent Category">'.GetOptions($Category, $this).Gdn_Format::Text($Category->Name).'</div>
            </li>';
            $Alt = FALSE;
         } else {
            $LastComment = UserBuilder($Category, 'Last');
            $AltCss = $Alt ? ' Alt' : '';
            $Alt = !$Alt;
            $CatList .= '<li id="Category_'.$CategoryID.'" class="'.$CssClass.'">
               <div class="ItemContent Category">'
                  .GetOptions($Category, $this)
                  .CategoryPhoto($Category)
                  .'<div class="TitleWrap">'
                     .Anchor(Gdn_Format::Text($Category->Name), CategoryUrl($Category), 'Title')
                  .'</div>
                  <div class="CategoryDescription">'
                  .$Category->Description
                  .'</div>
                  <div class="Meta">
                     <span class="MItem RSS">'.Anchor(Img('applications/dashboard/design/images/rss.gif'), '/categories/'.$Category->UrlCode.'/feed.rss').'</span>
                     <span class="MItem DiscussionCount">'.sprintf(Plural(number_format($Category->CountAllDiscussions), '%s discussion', '%s discussions'), $Category->CountDiscussions).'</span>
                     <span class="MItem CommentCount">'.sprintf(Plural(number_format($Category->CountAllComments), '%s comment', '%s comments'), $Category->CountComments).'</span>';
                     if ($Category->LastTitle != '') {
                        $CatList .= '<span class="MItem LastDiscussionTitle">'.sprintf(
                              T('Most recent: %1$s by %2$s'),
                              Anchor(Gdn_Format::Text(SliceString($Category->LastTitle, 40)), $Category->LastUrl),
                              UserAnchor($LastComment)
                           ).'</span>'
                           .'<span class="MItem LastCommentDate">'.Gdn_Format::Date($Category->LastDateInserted).'</span>';
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