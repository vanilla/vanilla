<?php if (!defined('APPLICATION')) exit();
// Create some variables so that they aren't defined in every loop.
$ViewLocation = $this->FetchViewLocation('discussions', 'discussions', 'vanilla');
?>
<ul class="DataList Discussions">
   <?php include($ViewLocation); ?>
</ul>
<?php
echo $this->Pager->ToString('more');