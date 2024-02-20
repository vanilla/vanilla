<?php if (!defined('APPLICATION')) exit(); ?>
<style>
   .CountItemWrap {
      width: <?php echo round(100 / (2 + count($this->data('ReactionTypes', [])))).'%'; ?>
   }
</style>

<?php
include_once 'reaction_functions.php';

echo wrap($this->data('Title'), 'h1 class="H"');

echo Gdn_Theme::module('BestOfFilterModule');

echo '<div class="BestOfData">';
include 'datalist.php';
echo '</div>';
