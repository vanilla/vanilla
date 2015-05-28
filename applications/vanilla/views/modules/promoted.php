<?php if (!defined('APPLICATION')) exit();
require_once Gdn::Controller()->FetchViewLocation('helper_functions', 'modules', 'vanilla');

?>
<h1 class="H"><?php echo $this->Data('Title'); ?></h1>
<?php

if ($data = $this->Data('Content')) {
   if ($view = $this->Data('View') == 'table') {
      writePromotedContentTable($data);
   } else {
      writePromotedContentList($data);
   }
} else {
   echo $this->Data('EmptyMessage');
}
