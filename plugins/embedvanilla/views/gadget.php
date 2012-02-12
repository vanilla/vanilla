<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Blogger Gadget'); ?></h1>
<div class="Info">
   <?php
   echo T('You can embed your Vanilla Forum into Blogger with this Google Gadget. When in design mode in Blogger, click to "Add a Gadget", and when prompted to search for one, select "Add your own".');
   echo Img('plugins/embedvanilla/design/gadgethelp.png', array('class' => 'Screenshot', 'alt' => 'Blogger Instructions'));
   echo T('Finally, enter this gadget url and click "Add by Url":');
   echo '<input type="text" value="'.Url('plugin/gadget', TRUE).'" class="GadgetInput" />';
   ?>   
</div>