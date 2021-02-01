<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) { exit(); } ?>
<div class="DataBox DataBox-AcceptedAnswers"><span id="accepted"></span>
    <?php BoxThemeShim::startHeading(); ?>
    <h2 class="CommentHeading"><?php echo plural(count($sender->data('Answers')), 'Best Answer', 'Best Answers'); ?></h2>
    <?php BoxThemeShim::endHeading(); ?>
    <ul class="MessageList DataList AcceptedAnswers pageBox">
        <?php
        foreach ($sender->data('Answers') as $Row) {
            $sender->EventArguments['Comment'] = $Row;
            writeComment($Row, $sender, Gdn::session(), 0);
        }
        ?>
    </ul>
</div>
