<?php if (!defined('APPLICATION')) exit();
echo '<h1>'.$this->Data('Title', T('Awaiting Moderation')).'<h1>';
?>
<div class="Info">
<?php
echo '<div>', T("Your post will appear once it's been approved."), '</div>';
?>
</div>