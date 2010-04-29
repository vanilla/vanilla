<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo T('Preview'); ?></h2>
<div class="Preview">
   <?php if (property_exists($this, 'Discussion')) { ?>
   <h2><?php echo Format::Text($this->Discussion->Name); ?></h2>
   <?php } ?>
   <ul class="MessageList Discussion">
      <li class="Item Comment">
         <div class="Meta">
            <span class="Author"><?php
               $Author = UserBuilder($this->Comment, 'Insert');
               echo UserPhoto($Author);
               echo UserAnchor($Author);
            ?></span>
            <span class="DateCreated"><?php echo Format::Date($this->Comment->DateInserted); ?></span>
         </div>
         <div class="Message"><?php echo Format::To($this->Comment->Body, Gdn::Config('Garden.InputFormatter')); ?></div>
      </li>
   </ul>
</div>