<?php if (!defined('APPLICATION')) exit(); ?>

<p><?php echo T('Hang on while we get your old data. This should take a few minutes...'); ?></p>
<div class="ImportProgress"><?php echo $this->Message; ?></div>
<?php
if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
   echo Anchor('Next', $this->RedirectUrl);