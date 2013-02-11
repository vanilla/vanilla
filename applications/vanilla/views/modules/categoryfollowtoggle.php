<?php if (!defined('APPLICATION')) exit();
$ShowAllCategoriesPref = Gdn::Session()->GetPreference('ShowAllCategories');
$Url = Gdn::Request()->Path();
?>

<div class="CategoryFilter">
   <div class="CategoryFilterTitle"><?php echo T('Category Filter'); ?></div>
   <div class="CategoryFilterOptions">
      <?php echo Wrap(T('Viewing'), 'span').': '; ?>
      <?php 
      if ($ShowAllCategoriesPref):
         echo Wrap(T('all categories'), 'span', array('class' => 'CurrentFilter'));
         echo ' | ';
         echo Wrap(Anchor(T('followed categories'), $Url.'?ShowAllCategories=false'), 'span');
      else:
         echo Wrap(Anchor(T('all categories'), $Url.'?ShowAllCategories=true'), 'span');
         echo ' | ';
         echo Wrap(T('followed categories'), 'span', array('class' => 'CurrentFilter'));
      endif;
      ?>
   </div>
</div>