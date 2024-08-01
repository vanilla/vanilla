<?php if (!defined('APPLICATION')) exit(); ?>
<style>
   .CountItemWrap {
      width: <?php echo round(100 / (2 + count($this->data('ReactionTypes', [])))).'%'; ?>
   }
</style>

<?php
include_once 'reaction_functions.php';

echo wrap($this->data('Title'), 'h1 class="H"');

echo '<div class="DataCounts">';
   $CurrentReactionType = $this->data('CurrentReaction');
   echo reactionFilterButton(t('Everything'), 'Everything', $CurrentReactionType);
   $ReactionTypeData = $this->data('ReactionTypes');
   foreach ($ReactionTypeData as $Key => $ReactionType) {
      echo reactionFilterButton(t(getValue('Name', $ReactionType, '')), getValue('UrlCode', $ReactionType, ''), $CurrentReactionType);
   }
echo '</div>

<div class="BestOfData">';
include_once('datalist.php');
echo '</div>';
