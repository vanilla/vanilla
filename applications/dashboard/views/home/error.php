<?php if (!defined('APPLICATION')) exit(); ?>
<div class="SplashInfo">
   <div class="Center">
      <h1><?php echo $this->Data('Title', T('Whoops!')); ?></h1>
      <div id="Message">
         <?php
         echo Gdn_Format::Markdown($this->Data('Exception', 'Add your message here foo.'));
         ?>
      </div>
   </div>
   <?php if (Debug() && $this->Data('Trace')): ?>
   <h2>Error</h2>
   <?php echo $this->Data('Code').' '.htmlspecialchars(Gdn_Controller::GetStatusMessage($this->Data('Code'))); ?>
   <h2>Trace</h2>
      <pre stye="text-align"><?php echo htmlspecialchars($this->Data('Trace')); ?></pre>
   <?php endif; ?>
   <!-- Code: <?php $this->Data('Code', 400); ?> -->
</div>