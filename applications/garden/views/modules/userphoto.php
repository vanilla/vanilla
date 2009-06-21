<?php if (!defined('APPLICATION')) exit();
if ($this->User->PhotoID > 0) {
   ?>
   <div class="Photo">
      <?php echo Html::Image('uploads/p'.$this->User->Photo); ?>
   </div>
   <?php
}
