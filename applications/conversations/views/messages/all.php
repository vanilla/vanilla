<?php if (!defined('APPLICATION')) exit();
?>
<h1 class="H"><?php echo $this->Data('Title'); ?></h1>
<?php

// Pager setup
$PagerOptions = array('CurrentRecords' => $this->Data('Conversations')->NumRows());
if ($this->Data('_PagerUrl'))
   $PagerOptions['Url'] = $this->Data('_PagerUrl');

// Pre Pager
PagerModule::Write($PagerOptions);

if ($this->Data('Conversations')->NumRows() > 0): ?>
   <ul class="Condensed DataList Conversations">
      <?php
      $ViewLocation = $this->FetchViewLocation('conversations');
      include($ViewLocation);
      ?>
   </ul>
<?php

else:
   
   echo '<div class="Empty">'.T('You do not have any conversations.').'</div>';

endif;

// Post Pager
PagerModule::Write($PagerOptions);
