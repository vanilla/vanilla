<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box BoxDrafts">
    <?php echo panelHeading(t('My Drafts')); ?>
    <ul class="PanelInfo PanelDiscussions">
        <?php foreach ($this->Data->result() as $Draft) {
            $EditUrl = !is_numeric($Draft->DiscussionID) || $Draft->DiscussionID <= 0 ? '/post/editdiscussion/0/'.$Draft->DraftID : '/post/editcomment/0/'.$Draft->DraftID;
            ?>
            <li>
                <strong><?php echo anchor($Draft->Name, $EditUrl); ?></strong>
                <?php echo anchor(sliceString(Gdn_Format::text($Draft->Body), 200), $EditUrl, 'DraftCommentLink'); ?>
            </li>
        <?php
        }
        ?>
        <li class="ShowAll"><?php echo anchor(t('↳ Show All'), 'drafts'); ?></li>
    </ul>
</div>
