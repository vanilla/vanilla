<?php if (!defined('APPLICATION')) exit; ?>

<ul class="SiteTotals">
    <h2><?php echo t('Forum Stats'); ?></h2>
    <?php foreach ($this->data('Totals') as $Name => $Value): ?>
        <li class="SiteTotal-Row">
      <span class="SiteTotal-Number">
         <?php echo number_format($Value); ?>
      </span>
      <span class="SiteTotal-Label">
         <?php echo plural($Value, $Name, $Name.'s'); ?>
      </span>
        </li>
    <?php endforeach; ?>
</ul>
