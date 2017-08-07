<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
helpAsset(t('Need More Help?'), $links);
?>
    <h1><?php echo t('Manage Categories'); ?></h1>
    <div class="Info">
        <?php
            echo t(
                'Categories are used to organize discussions.',
                'Categories are used to help your users organize their discussions in a way that is meaningful for your community.'
            ),
            ' ',
            t('Drag and drop the categories below to sort and nest them.');
        ?>
    </div>
    <div class="FilterMenu"><?php
        if (c('Vanilla.Categories.Use')) {
            echo anchor(t('Add Category'), 'vanilla/settings/addcategory', 'SmallButton');
            if (checkPermission('Garden.Settings.Manage')) {
                echo wrap(anchor(t("Don't use Categories"), 'vanilla/settings/enablecategories?enabled=0', 'SmallButton Hijack'));
            }
        } elseif (checkPermission('Garden.Settings.Manage')) {
            echo anchor(t('Use Categories'), 'vanilla/settings/enablecategories?enabled=1'.Gdn::session()->transientKey(), 'SmallButton Hijack');
        }
        ?></div>
<?php
if (c('Vanilla.Categories.Use')) {
    
    $desc = '<ul>';
    $desc .= '<li>'.sprintf(t('You can make the categories page your homepage.', 'You can make your categories page your homepage <a href="%s">here</a>.'), url('/dashboard/settings/homepage')).'</li>';
    $desc .= '<li>'.sprintf(t('Make sure you click View Page', 'Make sure you click <a href="%s">View Page</a> to see what your categories page looks like after saving.'), url('/categories')).'</li>';
    $desc .= '<li>'.t('Drag and drop the categories below to sort and nest them.').'</li>';
    $desc .= '</ul>';

    helpAsset(t('Did You Know?'), $desc);

    ?>
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
        .wrap(sprintf(
            t('Vanilla.Categories.MaxDisplayDepth', 'Place nested categories in a comma-delimited list when they are %1$s'),
            $this->Form->dropDown('Vanilla.Categories.MaxDisplayDepth', val('MaxDepthData', $this->Data))
        ), 'div')
        .wrap($this->Form->checkBox('Vanilla.Categories.DoHeadings', 'Display root categories as headings.'), 'div')
        .wrap($this->Form->checkBox('Vanilla.Categories.HideModule', 'Do not display the categories in the side panel.'), 'div')
        .'</div>'
        .'<div class="Buttons Wrap">'
        .$this->Form->button('Save')
        .'</div>'
        .$this->Form->close();

    echo wrap(t('Organize Categories'), 'h1')
        .'<ol class="Sortable">';
    $Right = []; // Start with an empty $Right stack
    $LastRight = 0;
    $OpenCount = 0;
    $Loop = 0;
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
            $CategoryUrl = categoryUrl($Category);

            if ($Category->Photo) {
                $Photo = img(Gdn_Upload::url($Category->Photo), ['class' => 'CategoryPhoto']);
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
                     '.wrap($Category->Description, 'blockquote').'
                     './*Wrap("ID: {$Category->CategoryID}, PermID: {$Category->PermissionCategoryID}", 'div').*/
                '
                  </td>
                  <td class="Buttons">'
                .anchor(t('Edit'), 'vanilla/settings/editcategory/'.$Category->CategoryID, 'SmallButton')
                .anchor(t('Move'), "vanilla/settings/movecategory/{$Category->CategoryID}", 'js-modal SmallButton')
                .(val('CanDelete', $Category) ? anchor(t('Delete'), 'vanilla/settings/deletecategory/'.$Category->CategoryID, 'SmallButton') : '')
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
