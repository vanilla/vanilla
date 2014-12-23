<?php if (!defined('APPLICATION')) exit(); ?>
<div class="P">
   <?php echo T('You can either ask a question or start a discussion.', 'You can either ask a question or start a discussion. Choose what you want to do below.'); ?>
</div>
<style>.NoScript { display: none; }</style>
<noscript>
   <style>.NoScript { display: block; } .YesScript { display: none; }</style>
</noscript>
<div class="P NoScript">
   <?php echo $Form->RadioList('Type', array('Question' => T('Ask a Question'), 'Discussion' => T('Start a New Discussion'))); ?>
</div>
<div class="YesScript">
   <div class="Tabs">
      <ul>
         <li class="<?php echo $Form->GetValue('Type') == 'Question' ? 'Active' : '' ?>"><a id="QnA_Question" class="QnAButton TabLink" rel="Question" href="#"><?php echo T('Ask a Question'); ?></a></li>
         <li class="<?php echo $Form->GetValue('Type') == 'Discussion' ? 'Active' : '' ?>"><a id="QnA_Discussion" class="QnAButton TabLink" rel="Discussion" href="#"><?php echo T('Start a New Discussion'); ?></a></li>
      </ul>
   </div>
</div>
