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
$MaxDisplayDepth = c('Vanilla.Categories.MaxDisplayDepth') + $this->data('Category.Depth', 0);
$ChildCategories = '';

$CategoryTree = $this->data('CategoryTree');
$Categories = CategoryModel::flattenTree($CategoryTree);
$this->EventArguments['NumRows'] = $Categories;

echo '<ul class="DataList CategoryList'.($DoHeadings ? ' CategoryListWithHeadings' : '').'">';
foreach ($Categories as $CategoryRow) {
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
               <div class="ItemContent Category"><div class="Options">'.getOptions($Category, $this).'</div>'.Gdn_Format::text($Category->Name).'</div>
            </li>';
        } else {
            $LastComment = UserBuilder($Category, 'Last');
            $CatList .= '<li id="Category_'.$CategoryID.'" class="'.$CssClass.'">
               <div class="ItemContent Category">'
                .'<div class="Options">'
                .getOptions($Category, $this)
                .'</div>'
                .CategoryPhoto($Category)
                .'<div class="TitleWrap">'
                .anchor(Gdn_Format::text($Category->Name), CategoryUrl($Category), 'Title')
                .'</div>
                  <div class="CategoryDescription">'
                .$Category->Description
                .'</div>
                  <div class="Meta">
                     <span class="MItem RSS">'.anchor(img('applications/dashboard/design/images/rss.gif', array('alt' => T('RSS Feed'))), '/categories/'.$Category->UrlCode.'/feed.rss', '', array('title' => T('RSS Feed'))).'</span>
                     <span class="MItem DiscussionCount">'.
                     sprintf(PluralTranslate($Category->CountDiscussions, '%s discussion html', '%s discussions html', t('%s discussion'), t('%s discussions')), BigPlural($Category->CountDiscussions, '%s discussion'))
                     .'</span>
                     <span class="MItem CommentCount">'.
                     sprintf(PluralTranslate($Category->CountComments, '%s comment html', '%s comments html', t('%s comment'), t('%s comments')), BigPlural($Category->CountComments, '%s comment'))
                     .'</span>';
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
            if (
                !in_array($Category->DisplayAs, ['Flat'])
                && $MaxDisplayDepth > 0
                && $Category->Depth == $MaxDisplayDepth - 1
                && $Category->TreeRight - $Category->TreeLeft > 1
            ) {
                $CatList .= '{ChildCategories}';
            }

            $CatList .= '</div>
               </div>
            </li>';
        }
    }
}
// If there are any remaining child categories that have been collected, do
// the replacement one last time.
if ($ChildCategories != '') {
    $CatList = str_replace('{ChildCategories}', '<span class="ChildCategories">'.Wrap(t('Child Categories:'), 'b').' '.$ChildCategories.'</span>', $CatList);
}

echo $CatList;
?>
</ul>
