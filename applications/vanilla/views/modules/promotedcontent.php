<?php if (!defined('APPLICATION')) exit();
require_once $this->FetchViewLocation('helper_functions');

?>
<div class="Box BoxPromoted">
   <h4><?php echo T('Promoted Content'); ?></h4>
   <div class="PanelInfo DataList">
      <?php
      $Content = $this->Data('Content');
      $ContentItems = sizeof($Content);
      
      if ($Content):
         
         if ($this->Group):
            $Content = array_chunk($Content, $this->Group);
         endif;

         foreach ($Content as $ContentChunk):
            if ($this->Group):
               echo '<div class="PromotedGroup">';
               foreach ($ContentChunk as $ContentItem):
                  WritePromotedContent($ContentItem, $this);
               endforeach;
               echo '</div>';
            else:
               WritePromotedContent($ContentChunk, $this);
            endif;
         endforeach;
         
      endif;
      ?>
   </div>
</div>