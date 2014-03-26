<?php if (!defined('APPLICATION')) exit(); 
require_once $this->FetchViewLocation('helper_functions');
?>

<h1 class="H"><?php echo $this->Data('Title'); ?></h1>
<div class="P PageDescription">
   <?php
   echo $this->Data('_Description');
   ?>
</div>

<div class="PageControls Top">
   <?php
   PagerModule::Write();
   ?>
</div>

<?php
if (C('Vanilla.Discussions.Layout') == 'table'):
   if (!function_exists('WriteDiscussionHeading'))
      require_once $this->FetchViewLocation('table_functions');
   ?>
   <div class="DataTableWrap">
   <table class="DataTable DiscussionsTable">
      <thead>
         <?php
         WriteDiscussionHeading();
         ?>
      </thead>
      <tbody>
      <?php
         foreach ($this->DiscussionData->Result() as $Discussion) {
            WriteDiscussionRow($Discussion, $this, Gdn::Session(), FALSE);
         }	
      ?>
      </tbody>
   </table>
   </div>
   <?php
else:
   ?>
   <ul class="DataList Discussions">
      <?php include($this->FetchViewLocation('discussions')); ?>
   </ul>
   <?php
endif;

?>
<div class="PageControls Bottom">
   <?php
   PagerModule::Write();
   ?>
</div>