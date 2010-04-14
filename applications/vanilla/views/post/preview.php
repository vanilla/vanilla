<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Preview">
   <div class="Message"><?php echo Gdn_Format::To($this->Comment->Body, Gdn::Config('Garden.InputFormatter')); ?></div>
</div>