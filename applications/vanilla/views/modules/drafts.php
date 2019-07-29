<?php use Vanilla\Formatting\Formats\TextFormat;

if (!defined('APPLICATION')) exit(); ?>
<div class="Box BoxDrafts">
    <?php echo panelHeading(t('My Drafts')); ?>
    <ul class="PanelInfo PanelDiscussions">
        <?php foreach ($this->Data->result() as $Draft) {
            $EditUrl = !is_numeric($Draft->DiscussionID) || $Draft->DiscussionID <= 0 ? '/post/editdiscussion/0/'.$Draft->DraftID : '/post/editcomment/0/'.$Draft->DraftID;
            ?>
            <li>
                <strong><?php echo anchor(htmlspecialchars($Draft->Name), $EditUrl); ?></strong>
                <?php echo anchor(
                        Gdn::formatService()->renderExcerpt(
                                $Draft->Body ?? '',
                                $Draft->Format ?? TextFormat::FORMAT_KEY
                        ),
                        $EditUrl,
                        'DraftCommentLink'
                ); ?>
            </li>
        <?php
        }
        ?>
        <li class="ShowAll"><?php echo anchor(t('↳ Show All'), 'drafts'); ?></li>
    </ul>
</div>
