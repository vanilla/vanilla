<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
    <style>
        .CategoryPhoto {
            float: left;
            margin-right: 8px;
            max-height: 64px;
            max-width: 64px;
        }
    </style>
    <div class="Help Aside">
        <?php
        echo wrap(t('Need More Help?'), 'h2');
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on managing categories"), 'settings/tutorials/category-management-and-advanced-settings'), 'li');
        echo wrap(Anchor(t('Managing Categories'), 'http://docs.vanillaforums.com/features/categories/'), 'li');
        echo '</ul>';
        ?>
    </div>
    <h1><?php echo t('Manage Categories'); ?></h1>
    <div class="Info">
        <?php echo t('Categories are used to help organize discussions.', 'Categories are used to help organize discussions. Drag &amp; drop the categories to sort and nest them.'); ?>
    </div>
    <div class="FilterMenu"><?php
        if (c('Vanilla.Categories.Use')) {
            echo anchor(t('Add Category'), 'vanilla/settings/addcategory', 'SmallButton');
            if (checkPermission('Garden.Settings.Manage')) {
                echo wrap(Anchor(t("Don't use Categories"), 'vanilla/settings/enablecategories?enabled=0', 'SmallButton Hijack'));
            }
        } elseif (checkPermission('Garden.Settings.Manage')) {
            echo anchor(t('Use Categories'), 'vanilla/settings/enablecategories?enabled=1'.Gdn::session()->TransientKey(), 'SmallButton Hijack');
        }
        ?></div>
<?php
if (c('Vanilla.Categories.Use')) {
    ?>
    <div class="Help Aside">
        <?php
        echo '<h2>', t('Did You Know?'), '</h2>';
        echo '<ul>';
        echo '<li>', sprintf(t('You can make the categories page your homepage.', 'You can make your categories page your homepage <a href="%s">here</a>.'), url('/dashboard/settings/homepage')), '</li>';
        echo '<li>', sprintf(t('Make sure you click View Page', 'Make sure you click <a href="%s">View Page</a> to see what your categories page looks like after saving.'), url('/categories')), '</li>';
        echo '<li>', t('Drag and drop the categories below to sort and nest them.'), '</li>';
        echo '</ul>';
        ?>
    </div>
    <h1><?php
        echo t('Category Page Layout');
        echo ' ';
        echo anchor(t('View Page'), 'categories');
        ?></h1>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    echo '<div class="Info">'
        .t('Configure how nested categories are displayed to users.')
        .Wrap(sprintf(
            t('Vanilla.Categories.MaxDisplayDepth', 'Place nested categories in a comma-delimited list when they are %1$s'),
            $this->Form->DropDown('Vanilla.Categories.MaxDisplayDepth', val('MaxDepthData', $this->Data))
        ), 'div')
        .Wrap($this->Form->CheckBox('Vanilla.Categories.DoHeadings', 'Display root categories as headings.'), 'div')
        .Wrap($this->Form->CheckBox('Vanilla.Categories.HideModule', 'Do not display the categories in the side panel.'), 'div')
        .'</div>'
        .'<div class="Buttons Wrap">'
        .$this->Form->button('Save')
        .'</div>'
        .$this->Form->close();

    echo wrap(t('Organize Categories'), 'h1')
        .'<ol class="Sortable">';
    $Right = array(); // Start with an empty $Right stack
    $LastRight = 0;
    $OpenCount = 0;
    $Loop = 0;
    $CanDelete = checkPermission('Garden.Settings.Manage');
    foreach ($this->CategoryData->result() as $Category) {
        if ($Category->CategoryID > 0) {
            // Only check stack if there is one
            $CountRight = count($Right);
            if ($CountRight > 0) {
                // Check if we should remove a node from the stack
                while (array_key_exists($CountRight - 1, $Right) && $Right[$CountRight - 1] < $Category->TreeRight) {
                    array_pop($Right);
                    $CountRight--;
                }
            }

            // Are we opening a new list?
            if ($CountRight > $LastRight) {
                $OpenCount++;
                echo "\n<ol>";
            } elseif ($OpenCount > $CountRight) {
                // Or are we closing open list and list items?
                while ($OpenCount > $CountRight) {
                    $OpenCount--;
                    echo "</li>\n</ol>\n";
                }
                echo '</li>';
            } elseif ($Loop > 0) {
                // Or are we closing an open list item?
                echo "</li>";
            }

            echo "\n".'<li id="list_'.$Category->CategoryID.'">';
            // DEBUG: echo wrap($Category->Name.' [countright: '.$CountRight.' lastcount: '.$LastRight.' opencount: '.$OpenCount.']', 'div');
            $CategoryUrl = CategoryUrl($Category);

            if ($Category->Photo) {
                $Photo = img(Gdn_Upload::url($Category->Photo), array('class' => 'CategoryPhoto'));
            } else {
                $Photo = '';
            }

            echo wrap(
                '<table'.($OpenCount > 0 ? ' class="Indented"' : '').'>
               <tr>
                  <td>
                     '.$Photo.'
                     <strong>'.htmlspecialchars($Category->Name).'</strong>
                     '.anchor(htmlspecialchars(rawurldecode($CategoryUrl)), $CategoryUrl).'
                     '.Wrap($Category->Description, 'blockquote').'
                     './*Wrap("ID: {$Category->CategoryID}, PermID: {$Category->PermissionCategoryID}", 'div').*/
                '
                  </td>
                  <td class="Buttons">'
                .anchor(t('Edit'), 'vanilla/settings/editcategory/'.$Category->CategoryID, 'SmallButton')
                .($CanDelete ? anchor(t('Delete'), 'vanilla/settings/deletecategory/'.$Category->CategoryID, 'SmallButton') : '')
                .'</td>
               </tr>
            </table>'
                , 'div');

            // Add this node to the stack
            $Right[] = $Category->TreeRight;
            $LastRight = $CountRight;
            $Loop++;
        }
    }
    if ($OpenCount > 0)
        echo "</li>\n</ol>\n</li>\n";
    else
        echo "</li>\n";

    echo '</ol>';
}
