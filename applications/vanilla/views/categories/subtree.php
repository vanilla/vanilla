<?php

$Category = $this->data('Category');
if (!$Category)
    return;

$SubCategories = CategoryModel::MakeTree(CategoryModel::categories(), $Category);

if (!$SubCategories)
    return;

require_once $this->fetchViewLocation('helper_functions', 'categories', 'vanilla');

?>
<h2 class="ChildCategories-Title Hidden"><?php echo t('Child Categories'); ?></h2>
<ul class="DataList ChildCategoryList">
    <?php
    foreach ($SubCategories as $Row):
        if (!$Row['PermsDiscussionsView'] || $Row['Archived'])
            continue;

        $Row['Depth'] = 1;
        ?>
        <li id="Category_<?php echo $Row['CategoryID']; ?>" class="Item Category">
            <div class="ItemContent Category">
                <h3 class="CategoryName TitleWrap"><?php
                    echo anchor(htmlspecialchars($Row['Name']), $Row['Url'], 'Title');
                    Gdn::controller()->EventArguments['Category'] = $Row;
                    Gdn::controller()->fireEvent('AfterCategoryTitle');
                    ?></h3>

                <?php if ($Row['Description']): ?>
                    <div class="CategoryDescription">
                        <?php echo $Row['Description']; ?>
                    </div>
                <?php endif; ?>

                <div class="Meta Hidden">
               <span class="MItem MItem-Count DiscussionCount"><?php
                   echo plural(
                       $Row['CountDiscussions'],
                       '%s discussion',
                       '%s discussions',
                       Gdn_Format::BigNumber($Row['CountDiscussions'], 'html'));
                   ?></span>

               <span class="MItem MItem-Count CommentCount"><?php
                   echo plural(
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
