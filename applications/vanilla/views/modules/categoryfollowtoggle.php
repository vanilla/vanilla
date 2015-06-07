<?php if (!defined('APPLICATION')) exit();
$ShowAllCategoriesPref = Gdn::session()->GetPreference('ShowAllCategories');
$Url = Gdn::request()->Path();
?>

<div class="CategoryFilter">
    <div class="CategoryFilterTitle"><?php echo t('Category Filter'); ?></div>
    <div class="CategoryFilterOptions">
        <?php echo wrap(t('Viewing'), 'span').': '; ?>
        <?php
        if ($ShowAllCategoriesPref):
            echo wrap(t('all categories'), 'span', array('class' => 'CurrentFilter'));
            echo ' | ';
            echo wrap(Anchor(t('followed categories'), $Url.'?ShowAllCategories=false'), 'span');
        else:
            echo wrap(Anchor(t('all categories'), $Url.'?ShowAllCategories=true'), 'span');
            echo ' | ';
            echo wrap(t('followed categories'), 'span', array('class' => 'CurrentFilter'));
        endif;
        ?>
    </div>
</div>
