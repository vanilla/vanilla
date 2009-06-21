<?php if (!defined('APPLICATION')) exit();
if (property_exists($this, 'Discussion')) { ?>
<h2><?php echo Format::Text($this->Discussion->Name); ?></h2>
<?php } ?>
<ul class="Discussion Preview">
   <li class="Comment">
      <ul class="Info Mine">
         <li class="Author"><?php 
            echo UserPhoto($this->Comment->InsertName, $this->Comment->InsertPhoto);
            echo UserAnchor($this->Comment->InsertName);
         ?></li>
         <li class="Created"><?php echo Format::Date($this->Comment->DateInserted); ?></li>
      </ul>
      <div class="Body"><?php echo Format::To($this->Comment->Body, Gdn::Config('Garden.InputFormatter')); ?></div>
   </li>
</ul>