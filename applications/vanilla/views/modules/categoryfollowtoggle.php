<?php if (!defined('APPLICATION')) exit();
$ShowAllCategoriesPref = Gdn::session()->getPreference('ShowAllCategories');
$Url = Gdn::request()->path();
?>

<div class="CategoryFilter">
    <div class="CategoryFilterTitle"><?php echo t('Category Filter'); ?></div>
    <div class="CategoryFilterOptions">
        <?php echo wrap(t('Viewing'), 'span').': '; ?>
        <?php
        if ($ShowAllCategoriesPref):
            echo wrap(t('all categories'), 'span', ['class' => 'CurrentFilter']);
            echo ' | ';
            echo wrap(anchor(t('followed categories'), $Url.'?ShowAllCategories=false'), 'span');
        else:
            echo wrap(anchor(t('all categories'), $Url.'?ShowAllCategories=true'), 'span');
            echo ' | ';
            echo wrap(t('followed categories'), 'span', ['class' => 'CurrentFilter']);
        endif;
        ?>
    </div>
</div>
