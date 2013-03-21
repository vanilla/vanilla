<?php if (!defined('APPLICATION')) exit();
//require_once $this->FetchViewLocation('helper_functions');

?>
<div class="DiscussionSorter" style="float:right;">
   <span class="ToggleFlyout">
   <?php 
         echo Wrap(T('by Last Comment').' '.Sprite('SpMenu', 'Sprite Sprite16'), 'span', array('class' => 'Selected'));
   ?>
   <div class="Flyout MenuItems">
      <ul>
         <?php //echo Wrap(Anchor(T('by Last Comment'), '/'), 'li'); ?>
         <?php echo Wrap(Anchor(T('by Start Date'), '/', array('class' => 'SortByStartDate')), 'li'); ?>
      </ul>
   </div>
   </span>
</div>

<script>
jQuery(document).ready(function($) {
   $(document).delegate('.SortByStartDate', 'click', function() {
      // 
      var SortURL = gdn.url('discussions/sort');
      var SendData = {
            'TransientKey': gdn.definition('TransientKey'),
            'Path': gdn.definition('Path'), 
            'DiscussionSort': 'DateInserted'
         };
      
      jQuery.ajax({
         dataType: 'json',
         type: 'post',
         url: SortURL,
         data: SendData,
         success: function(json) {
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

<style>
   .DiscussionSorter {
      margin-right: 24px;
      font-size: 11px; }
   .DiscussionSorter:hover {
      cursor: pointer;
      background: #f3f3f3;
   }
   .DiscussionSorter .Selected {
      padding: 0 0 0 15px;
      display: inline-block;
      width: 115px;
      border: 1px solid transparent;
      border-bottom: none;  
   }
   .DiscussionSorter .Open .Selected {
      border: 1px solid #999;
      border-bottom: none;  
   }
   .SpMenu { 
      background-position: -32px -20px;
      height: 8px; 
      width: 12px; 
      margin-top: 8px; }
   .DiscussionSorter .Flyout:before, .Flyout:after { border: none; }
   .DiscussionSorter .MenuItems { padding: 0; border-top: none; width: 130px; border-radius: 0; }
   .DiscussionSorter .ToggleFlyout .Flyout { top: 18px; }
</style>