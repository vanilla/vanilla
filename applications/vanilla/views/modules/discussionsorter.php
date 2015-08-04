<?php if (!defined('APPLICATION')) exit(); ?>
<div class="DiscussionSorter">
   <span class="ToggleFlyout">
   <?php
   $Text = val($this->SortFieldSelected, $this->SortOptions);
   echo wrap($Text.' '.Sprite('SpMenu', 'Sprite Sprite16'), 'span', array('class' => 'Selected'));
   ?>
       <div class="Flyout MenuItems">
           <ul>
               <?php
               foreach ($this->SortOptions as $SortField => $SortText) {
                   if ($SortField != $this->SortFieldSelected)
                       echo wrap(Anchor($SortText, '#', array('class' => 'SortDiscussions', 'data-field' => $SortField)), 'li');
               }
               ?>
           </ul>
       </div>
   </span>
</div>
