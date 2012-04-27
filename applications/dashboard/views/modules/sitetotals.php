<?php if (!defined('APPLICATION')) exit; ?>

<ul class="SiteTotals">
   <h2><?php echo T('Forum Stats'); ?></h2>
   <?php foreach ($this->Data('Totals') as $Name => $Value): ?>
   <li class="SiteTotal-Row">
      <span class="SiteTotal-Number">
         <?php echo number_format($Value); ?>
      </span>
      <span class="SiteTotal-Label">
         <?php echo Plural($Value, $Name, $Name.'s'); ?>
      </span>
   </li>
   <?php endforeach; ?>
</ul>
