<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo T('Preview'); ?></h2>
<div class="Preview">
   <?php if (property_exists($this, 'Discussion')) { ?>
   <h2><?php echo Format::Text($this->Discussion->Name); ?></h2>
   <?php } ?>
   <ul class="Discussion">
      <li class="Comment">
         <ul class="Info">
            <li class="Author"><?php
               $Author = UserBuilder($this->Comment, 'Insert');
               echo UserPhoto($Author);
               echo UserAnchor($Author);
            ?></li>
            <li class="Created"><?php echo Format::Date($this->Comment->DateInserted); ?></li>
         </ul>
         <div class="Body"><?php echo Format::To($this->Comment->Body, Gdn::Config('Garden.InputFormatter')); ?></div>
      </li>
   </ul>
</div>