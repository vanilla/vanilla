<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Connect">
	<h1><?php echo StringIsNullOrEmpty($ConnectSource) ? T("Sign in") : sprintf(T('%s Connect'), $ConnectSource); ?></h1>
	<div class="Box">
	<?php
		echo $this->Form->Open();
		echo $this->Form->Errors();
		echo $this->Form->Close();
   ?>
	</div>
</div>