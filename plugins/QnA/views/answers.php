<?php if (!defined('APPLICATION')) { exit(); } ?>
<div class="DataBox DataBox-AcceptedAnswers"><span id="accepted"></span>
    <h2 class="CommentHeading"><?php echo plural(count($sender->data('Answers')), 'Best Answer', 'Best Answers'); ?></h2>
    <ul class="MessageList DataList AcceptedAnswers">
        <?php
        foreach ($sender->data('Answers') as $Row) {
            $sender->EventArguments['Comment'] = $Row;
            writeComment($Row, $sender, Gdn::session(), 0);
        }
        ?>
    </ul>
</div>
