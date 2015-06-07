<?php if (!defined('APPLICATION')) exit(); ?>
<div class="PageBox">
    <h3><?php echo anchor(htmlspecialchars($this->data('PageInfo.Title')), $this->data('PageInfo.Url')); ?></h3>

    <div class="Thumbnail">
        <?php
        foreach ($this->data('PageInfo.Images') as $Src) {
            echo img($Src);
        }
        ?>
    </div>
    <div class="Description">
        <?php echo htmlspecialchars($this->data('PageInfo.Description')); ?>
    </div>
</div>
