<?php if (!defined('APPLICATION')) exit(); ?>
<div class="DataBox DataBox-AcceptedAnswers"><span id="latest"></span>
   <h2 class="CommentHeading"><?php echo Plural(count($Sender->Data('Answers')), 'Best Answer', 'Best Answers'); ?></h2>
   <ul class="MessageList DataList AcceptedAnswers">
      <?php
      foreach($Sender->Data('Answers') as $Row) {
         $Sender->EventArguments['Comment'] = $Row;
         WriteComment($Row, $Sender, Gdn::Session(), 0);
      }
      ?>
   </ul>
</div>
