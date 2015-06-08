<?php if (!defined('APPLICATION')) exit();

if (!function_exists('GetOptions'))
    include $this->fetchViewLocation('helper_functions', 'categories');

echo '<h1 class="H HomepageTitle">'.$this->data('Title').'</h1>';
if ($Description = $this->Description()) {
    echo wrap($Description, 'div', array('class' => 'P PageDescription'));
}
$this->fireEvent('AfterPageTitle');

$CatList = '';
$DoHeadings = c('Vanilla.Categories.DoHeadings');
$MaxDisplayDepth = c('Vanilla.Categories.MaxDisplayDepth') + $this->data('Category')->Depth;
$ChildCategories = '';
$this->EventArguments['NumRows'] = count($this->data('Categories'));

//if (c('Vanilla.Categories.ShowTabs')) {
////   $ViewLocation = Gdn::controller()->fetchViewLocation('helper_functions', 'Discussions', 'vanilla');
////   include_once $ViewLocation;
////   WriteFilterTabs($this);
//   echo Gdn_Theme::Module('DiscussionFilterModule');
//}

echo '<ul class="DataList CategoryList'.($DoHeadings ? ' CategoryListWithHeadings' : '').'">';
$Alt = FALSE;
foreach ($this->data('Categories') as $CategoryRow) {
    $Category = (object)$CategoryRow;

    $this->EventArguments['CatList'] = &$CatList;
    $this->EventArguments['ChildCategories'] = &$ChildCategories;
    $this->EventArguments['Category'] = &$Category;
    $this->fireEvent('BeforeCategoryItem');
    $CssClass = CssClass($CategoryRow);

    $CategoryID = val('CategoryID', $Category);

    if ($Category->CategoryID > 0) {
        // If we are below the max depth, and there are some child categories
        // in the $ChildCategories variable, do the replacement.
        if ($Category->Depth < $MaxDisplayDepth && $ChildCategories != '') {
            $CatList = str_replace('{ChildCategories}', '<span class="ChildCategories">'.Wrap(t('Child Categories:'), 'b').' '.$ChildCategories.'</span>', $CatList);
            $ChildCategories = '';
        }

        if ($Category->Depth >= $MaxDisplayDepth && $MaxDisplayDepth > 0) {
            if ($ChildCategories != '')
                $ChildCategories .= ', ';
            $ChildCategories .= anchor(Gdn_Format::text($Category->Name), CategoryUrl($Category));
        } else if ($Category->DisplayAs === 'Heading') {
            $CatList .= '<li id="Category_'.$CategoryID.'" class="CategoryHeading '.$CssClass.'">
               <div class="ItemContent Category">'.GetOptions($Category, $this).Gdn_Format::text($Category->Name).'</div>
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
                .anchor(Gdn_Format::text($Category->Name), CategoryUrl($Category), 'Title')
                .'</div>
                  <div class="CategoryDescription">'
                .$Category->Description
                .'</div>
                  <div class="Meta">
                     <span class="MItem RSS">'.anchor(Img('applications/dashboard/design/images/rss.gif'), '/categories/'.$Category->UrlCode.'/feed.rss').'</span>
                     <span class="MItem DiscussionCount">'.sprintf(Plural(number_format($Category->CountAllDiscussions), '%s discussion', '%s discussions'), $Category->CountDiscussions).'</span>
                     <span class="MItem CommentCount">'.sprintf(Plural(number_format($Category->CountAllComments), '%s comment', '%s comments'), $Category->CountComments).'</span>';
            if ($Category->LastTitle != '') {
                $CatList .= '<span class="MItem LastDiscussionTitle">'.sprintf(
                        t('Most recent: %1$s by %2$s'),
                        anchor(Gdn_Format::text(sliceString($Category->LastTitle, 40)), $Category->LastUrl),
                        userAnchor($LastComment)
                    ).'</span>'
                    .'<span class="MItem LastCommentDate">'.Gdn_Format::date($Category->LastDateInserted).'</span>';
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
    $CatList = str_replace('{ChildCategories}', '<span class="ChildCategories">'.Wrap(t('Child Categories:'), 'b').' '.$ChildCategories.'</span>', $CatList);

echo $CatList;
?>
</ul>
