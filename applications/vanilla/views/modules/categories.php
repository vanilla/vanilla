<?php if (!defined('APPLICATION')) exit();
$CountDiscussions = 0;
$CategoryID = isset($this->_Sender->CategoryID) ? $this->_Sender->CategoryID : '';
$OnCategories = strtolower($this->_Sender->ControllerName) == 'categoriescontroller' && !is_numeric($CategoryID);
if ($this->Data !== FALSE) {
    foreach ($this->Data->result() as $Category) {
        $CountDiscussions = $CountDiscussions + $Category->CountDiscussions;
    }
    ?>
    <div class="Box BoxCategories">
        <?php echo panelHeading(t('Categories')); ?>
        <ul class="PanelInfo PanelCategories">
            <?php
            echo '<li'.($OnCategories ? ' class="Active"' : '').'>'.
                anchor('<span class="Aside"><span class="Count">'.bigPlural($CountDiscussions, '%s discussion').'</span></span> '.t('All Categories'), '/categories', 'ItemLink')
                .'</li>';

            $MaxDepth = c('Vanilla.Categories.MaxDisplayDepth');

            foreach ($this->Data->result() as $Category) {
                if ($Category->CategoryID < 0 || $MaxDepth > 0 && $Category->Depth > $MaxDepth)
                    continue;

                $attributes = false;

                if ($Category->DisplayAs === 'Heading') {

                    $CssClass = 'Heading '.$Category->CssClass;
                    $attributes = ['aria-level' => $Category->Depth + 2];
                } else {
                    $CssClass = 'Depth'.$Category->Depth.($CategoryID == $Category->CategoryID ? ' Active' : '').' '.$Category->CssClass;
                }


                if (is_array($attributes)) {
                    $attributes = attribute($attributes);
                }

                echo '<li class="ClearFix '.$CssClass.'" '.$attributes.'>';

                if ($Category->CountAllDiscussions > 0) {
                    $CountText = '<span class="Aside"><span class="Count">'.bigPlural($Category->CountAllDiscussions, '%s discussion').'</span></span>';
                } else {
                    $CountText = '';
                }

                if ($Category->DisplayAs === 'Heading') {
                    echo $CountText.' '.htmlspecialchars($Category->Name);
                } else {
                    echo anchor($CountText.' '.htmlspecialchars($Category->Name), categoryUrl($Category), 'ItemLink');
                }
                echo "</li>\n";
            }
            ?>
        </ul>
    </div>
<?php
}
