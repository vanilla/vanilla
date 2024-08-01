<?php if (!defined('APPLICATION')) { exit(); } ?>
<div class="P">
    <?php echo t('You can either ask a question or start a discussion.', 'You can either ask a question or start a discussion. Choose what you want to do below.'); ?>
</div>
<style>.NoScript { display: none; }</style>
<noscript>
    <style>.NoScript { display: block; } .YesScript { display: none; }</style>
</noscript>
<div class="P NoScript">
    <?php echo $Form->radioList('Type', ['Question' => t('Ask a Question'), 'Discussion' => t('Start a New Discussion')]); ?>
</div>
<div class="YesScript">
    <div class="Tabs">
        <ul>
            <li class="<?php echo $Form->getValue('Type') == 'Question' ? 'Active' : '' ?>"><a id="QnA_Question" class="QnAButton TabLink" rel="Question" href="#"><?php echo t('Ask a Question'); ?></a></li>
            <li class="<?php echo $Form->getValue('Type') == 'Discussion' ? 'Active' : '' ?>"><a id="QnA_Discussion" class="QnAButton TabLink" rel="Discussion" href="#"><?php echo t('Start a New Discussion'); ?></a></li>
        </ul>
    </div>
</div>
