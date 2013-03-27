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

<script>
jQuery(document).ready(function($) {
   $(document).undelegate('.SortDiscussions', 'click');
   $(document).delegate('.SortDiscussions', 'click', function() {
      // Gather data
      var SortOrder = $(this).attr('data-field');
      var SortURL = gdn.url('discussions/sort');
      var SendData = {
         'TransientKey': gdn.definition('TransientKey'),
         'Path': gdn.definition('Path'), 
         'DiscussionSort': SortOrder
      };
      
      jQuery.ajax({
         dataType: 'json',
         type: 'post',
         url: SortURL,
         data: SendData,
         success: function(json) {
            json = $.postParseJson(json);
            if (json.RedirectUrl) {
               $(frm).triggerHandler('complete');
               // Redirect to the new discussions list
               document.location = json.RedirectUrl;
            } else {
               $('#Content').html(json.Data);
            }
         }
      });
      
      return false;
   });
});
</script>
