<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Preview">
   <div class="Message"><?php echo Format::To($this->Comment->Body, Gdn::Config('Garden.InputFormatter')); ?></div>
</div>