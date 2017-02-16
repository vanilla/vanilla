<?php if (!defined('APPLICATION')) exit(); ?>

<div class="category-filter-container padded-top">
    <?php if ($this->getFilter()) {
        writeCategoryTree($this->data('Categories', []), 0, false);
    } ?>
</div>
