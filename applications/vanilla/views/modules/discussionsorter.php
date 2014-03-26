<?php if (!defined('APPLICATION')) exit(); ?>
<div class="DiscussionSorter">
   <span class="ToggleFlyout">
   <?php 
         $Text = GetValue($this->SortFieldSelected, $this->SortOptions);
         echo Wrap($Text.' '.Sprite('SpMenu', 'Sprite Sprite16'), 'span', array('class' => 'Selected'));
   ?>
   <div class="Flyout MenuItems">
      <ul>
         <?php 
            foreach ($this->SortOptions as $SortField => $SortText) {
               if ($SortField != $this->SortFieldSelected)
                  echo Wrap(Anchor($SortText, '#', array('class' => 'SortDiscussions', 'data-field' => $SortField)), 'li'); 
            }
         ?>
      </ul>
   </div>
   </span>
</div>
